<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\Logger;
use IServ\CrudBundle\Controller\StrictCrudController;
use IServ\CrudBundle\Entity\FlashMessage;
use IServ\Library\Config\Config;
use IServ\Library\PhpImapReplacement\PhpImapReplacement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\MailAliasBundle\Admin\AddressAdmin;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Stsbl\MailAliasBundle\Form\Type\ImportType;
use Stsbl\MailAliasBundle\Model\Import;
use Stsbl\MailAliasBundle\Service\Importer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Backend controller for Mail Alias Management
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class MailAliasController extends StrictCrudController
{
    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request)
    {
        $session = $this->getSession();

        $ret = parent::indexAction($request);

        if (is_array($ret)) {
            $ret['importForm'] = $this->createImportForm()->createView();

            $importMsg = $session->has('mailalias_import_msg');
            $ret['displayImportMessages'] = $importMsg;

            if ($importMsg) {
                $ret['importMessages'] = $session->get('mailalias_import_msg');
                $session->remove('mailalias_import_msg');
            }

            $importWarn = $session->has('mailalias_import_warnings');
            $ret['displayImportWarnings'] = $importWarn;

            if ($importWarn) {
                $ret['importWarnings'] = $session->get('mailalias_import_warnings');
                $this->get('session')->remove('mailalias_import_warnings');
            }
        }

        return $ret;
    }

    /**
     * Get auto-completion suggestions for users and groups
     *
     * @Route("admin/mailalias/recipients", name="admin_mailalias_recipients", options={"expose"=true}, methods={"GET"})
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
     */
    public function getRecipientsAutocompleteAction(Request $request, Config $config): JsonResponse
    {
        $type = $request->query->get('type');
        $query = $request->query->get('query');
        $suggestions = [];

        if ($type === null) {
            throw new \InvalidArgumentException('Parameter type should not be null.');
        }
        if ($type !== 'group' && $type !== 'user') {
            throw new \InvalidArgumentException(sprintf('Invalid type %s.', $type));
        }

        $host = $config->get('Domain');
        if ($type === 'group') {
            /* @var $groupRepo \IServ\CoreBundle\Entity\GroupRepository */
            $groupRepo = $this->getDoctrine()->getRepository('IServCoreBundle:Group');

            foreach ($groupRepo->addressLookup($query) as $group) {
                /* @var $group \IServ\CoreBundle\Entity\Group */
                $rfc822string = PhpImapReplacement::imap_rfc822_write_address($group->getAccount(), $host, $group->getName());
                $suggestions[] = ['label' => $group->getName(), 'value' => $rfc822string, 'type' => $type, 'extra' => _('Group')];
            }
        } elseif ($type === 'user') {
            $users = $this->userAddressLookup($query);

            foreach ($users as $user) {
                /* @var $user \IServ\CoreBundle\Entity\User */
                $rfc822string = PhpImapReplacement::imap_rfc822_write_address($user->getUsername(), $host, $user->getName());

                // determine extra + type
                if ($user->isAdmin()) {
                    $extra = _('Administrator');
                    $type = 'admin';
                } elseif ($user->hasRole('ROLE_TEACHER')) {
                    $extra = _('Teacher');
                    $type = 'teacher';
                } elseif ($user->hasRole('ROLE_STUDENT')) {
                    $extra = _('Student');
                    $type = 'student';
                } else {
                    $extra = _('User');
                    $type = 'user';
                }

                $label = $user->getName();
                if ($user->getAuxInfo() != null) {
                    $label .= ' (' . $user->getAuxInfo() . ')';
                }
                $suggestions[] = ['label' => $label, 'value' => $rfc822string, 'type' => $type, 'extra' => $extra];
            }
        }

        return new JsonResponse($suggestions);
    }

    /**
     * Imports a submitted csv file
     *
     * @Route("admin/mailalias/import", name="admin_mailalias_import")
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
     * @Template()
     *
     * @return array|RedirectResponse
     */
    public function importAction(Importer $importer, Logger $logger, Request $request, Config $config)
    {
        $session = $this->getSession();

        $form = $this->createImportForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Import $import */
            $import = $form->getData();

            try {
                $importer->transform($import);

                $warnings = $importer->getWarnings();

                if (!empty($warnings)) {
                    $session->set('mailalias_import_warnings', implode("\n", $warnings));
                }

                $servername = $config->get('Domain');
                $module = 'Mail aliases';
                $messages = [];

                /* @var $newAddresses \Stsbl\MailAliasBundle\Entity\Address[] */
                $newAddresses = $importer->getNewAddresses();
                foreach ($newAddresses as $address) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_ALIAS_ADDED, $address, $servername), $module);
                    $messages[] = __('Added alias %s@%s.', $address, $servername);

                    foreach ($address->getUsers() as $user) {
                        $logger->writeForModule(sprintf(
                            AddressAdmin::LOG_USER_RECIPIENT_ADDED,
                            $user,
                            $address,
                            $servername
                        ), $module);
                        $messages[] = __('Added user %s as recipient for alias %s@%s.', $user, $address, $servername);
                    }

                    foreach ($address->getGroups() as $group) {
                        $logger->writeForModule(sprintf(
                            AddressAdmin::LOG_GROUP_RECIPIENT_ADDED,
                            $group,
                            $address,
                            $servername
                        ), $module);
                        $messages[] = __('Added group %s as recipient for alias %s@%s.', $group, $address, $servername);
                    }
                }

                if (count($messages) > 0) {
                    $session->set('mailalias_import_msg', implode("\n", $messages));
                }

                return new RedirectResponse($this->generateUrl('admin_mailalias_index'));
            } catch (ImportException $e) {
                $message = $e->getMessage();
                $line = $e->getFileLine();

                if ($message === ImportException::MESSAGE_INVALID_COLUMN_AMOUNT) {
                    $message = str_replace('.', '', $message);
                    if (null !== $line) {
                        $message .= ' near line %s.';
                    } else {
                        $message .= '.';
                    }

                    $message = __($message, $line);
                } else {
                    $message = _($message);
                }

                $this->addFlash(new FlashMessage('error', $message));
            }
        }

        // track path
        $this->addBreadcrumb(_('Mail aliases'), $this->generateUrl('admin_mailalias_index'));
        $this->addBreadcrumb(_('Import'));

        return [
            'importForm' => $form->createView(),
            'importExplanation' => AddressAdmin::getImportExplanation(),
            'importExplanationFieldList' => AddressAdmin::getImportExplanationFieldList()
        ];
    }

    /**
     * Gets an form for csv import
     */
    private function createImportForm(): FormInterface
    {
        return $this->createForm(ImportType::class);
    }

    /**
     * Finds users for address lookup
     * Inspired by the function in GroupRepository,
     * but User has no similar function and also
     * seems to does not have even a repository.
     *
     * So, we have to implement the functions here.
     *
     * @param string $query
     * @return User[]
     */
    private function userAddressLookup(string $query): array
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getDoctrine()->getRepository(User::class)->createQueryBuilder('u');

        $qb->select('u');

        $terms = preg_split("/\s+/", trim($query));
        foreach ($terms as $index => $term) {
            $qb
                ->andWhere(
                    'LOWER(u.username) LIKE :adra' . $index . ' OR LOWER(u.username) LIKE :adr_mail' . $index . ' OR ' .
                    'LOWER(u.firstname) LIKE :adra' . $index . ' OR LOWER(u.firstname) LIKE :adrb' . $index . ' OR ' .
                    'LOWER(u.lastname) LIKE :adra' . $index . ' OR LOWER(u.lastname) LIKE :adrb' . $index
                )
                ->setParameter('adra' . $index, strtolower($term) . '%')
                ->setParameter('adrb' . $index, '% ' . strtolower($term) . '%')
                ->setParameter('adr_mail' . $index, '%.' . strtolower($term) . '%')
            ;
        }

        return $qb->getQuery()->setMaxResults(10)->getResult();
    }
}
