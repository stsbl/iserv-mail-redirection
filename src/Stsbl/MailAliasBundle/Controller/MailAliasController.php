<?php
// src/Stsbl/MailAliasBundle/Controler/MailAliasController.php
namespace Stsbl\MailAliasBundle\Controller;

use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CrudBundle\Controller\CrudController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\MailAliasBundle\Admin\AddressAdmin;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
class MailAliasController extends CrudController 
{
    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request) 
    {
        $ret = parent::indexAction($request);
        
        if (is_array($ret)) {
            $ret['importForm'] = $this->getImportForm()->createView();

            $importMsg = $this->get('session')->has('mailalias_import_msg');
            $ret['displayImportMessages'] = $importMsg;

            if ($importMsg) {
                $ret['importMessages'] = $this->get('session')->get('mailalias_import_msg');
                $this->get('session')->remove('mailalias_import_msg');
            }

            $importWarn = $this->get('session')->has('mailalias_import_warnings');
            $ret['displayImportWarnings'] = $importWarn;

            if ($importWarn) {
                $ret['importWarnings'] = $this->get('session')->get('mailalias_import_warnings');
                $this->get('session')->remove('mailalias_import_warnings');
            }
        }
        
        return $ret;
    }

    /**
     * Get auto-completion suggestions for users and groups
     * 
     * @Method("GET")
     * @Route("admin/mailaliases/recipients", name="admin_mailalias_recipients", options={"expose"=true})
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecipientsAutocomplete(Request $request)
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
        
        $host = $this->get('iserv.config')->get('Servername');
        if ($type === 'group') {
            /* @var $groupRepo \IServ\CoreBundle\Entity\GroupRepository */
            $groupRepo = $this->getDoctrine()->getManager()->getRepository('IServCoreBundle:Group');
            
            foreach ($groupRepo->addressLookup($query) as $group) {
                /* @var $group \IServ\CoreBundle\Entity\Group */ 
                $rfc822string = imap_rfc822_write_address($group->getAccount(), $host, $group->getName());
                $suggestions[] = ['label' => $group->getName(), 'value' => $rfc822string, 'type' => $type, 'extra' => _('Group')];
            }
        } else if ($type === 'user') {
            $users = $this->userAddressLookup($query);
            
            foreach ($users as $user) {
                /* @var $user \IServ\CoreBundle\Entity\User */ 
                $rfc822string = imap_rfc822_write_address($user->getUsername(), $host, $user->getName());
                
                // determine extra + type
                if ($user->isAdmin()) {
                    $extra = _('Administrator');
                    $type = 'admin';
                } else if ($user->hasRole('ROLE_TEACHER')) {
                    $extra = _('Teacher');
                    $type = 'teacher';
                } else if ($user->hasRole ('ROLE_STUDENT')) {
                    $extra = _('Student');
                    $type = 'student';
                } else {
                    $extra = _('User');
                    $type = 'user';
                }
                
                $label = $user->getName();
                if ($user->getAuxInfo() != null) {
                    $label .= ' ('.$user->getAuxInfo().')';
                }
                $suggestions[] = ['label' => $label, 'value' => $rfc822string, 'type' => $type, 'extra' => $extra];
            }
        }
        
        return new JsonResponse($suggestions);
    }
    
    /**
     * Imports a submitted csv file
     * 
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("admin/mailaliases/import", name="admin_mailalias_import")
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
     * @Template()
     */
    public function importAction(Request $request)
    {
        $form = $this->getImportForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            /* @var $importer \Stsbl\MailAliasBundle\Service\Importer */
            $importer = $this->get('stsbl.mailalias.service.importer');
            
            try {
                $importer->setEnableNewAliases((boolean)$data['enable']);
                $importer->setUploadedFile($data['file']);
                $importer->transform();
                
                $warnings = [];
                foreach ($importer->getWarnings() as $w) {
                    $warnings[] = $w;
                }
                
                if (count($warnings) > 0) {
                    $this->get('session')->set('mailalias_import_warnings', implode("\n", $warnings));
                }
                
                /* @var $logger \IServ\CoreBundle\Service\Logger */
                $logger = $this->get('iserv.logger');
                $servername = $this->get('iserv.config')->get('Servername');
                $module = 'Mail aliases';
                $messages = [];
                
                /* @var $newAddresses \Stsbl\MailAliasBundle\Entity\Address[] */
                $newAddresses = $importer->getNewAddresses();
                foreach ($newAddresses as $a) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_ALIAS_ADDED, (string)$a, $servername), $module);
                    $messages[] = __('Added alias %s@%s.', (string)$a, $servername);
                }
                
                /* @var $newUserRecipients \Stsbl\MailAliasBundle\Entity\UserRecipient[] */
                $newUserRecipients = $importer->getNewUserRecipients();
                foreach ($newUserRecipients as $u) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_USER_RECIPIENT_ADDED, (string)$u, (string)$u->getOriginalRecipient(), $servername), $module);
                    $messages[] = __('Added user %s as recipient for alias %s@%s.', (string)$u, (string)$u->getOriginalRecipient(), $servername);
                }
                
                /* @var $newGroupRecipients \Stsbl\MailAliasBundle\Entity\GroupRecipient[] */
                $newGroupRecipients = $importer->getNewGroupRecipients();
                foreach ($newGroupRecipients as $g) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_GROUP_RECIPIENT_ADDED, (string)$g, (string)$g->getOriginalRecipient(), $servername), $module);
                    $messages[] = __('Added group %s as recipient for alias %s@%s.', (string)$g, (string)$g->getOriginalRecipient(), $servername);
                }
                
                if (count($messages) > 0) {
                    $this->get('session')->set('mailalias_import_msg', implode("\n", $messages));
                }

                return new RedirectResponse($this->generateUrl('admin_mailalias_index'));
            } catch (ImportException $e) {
                $message = $e->getMessage();
                $line = $e->getFileLine();
                
                if ($message === ImportException::MESSAGE_INVALID_COLUMN_AMOUNT) {
                    $message = str_replace('.', '', $message);
                    if (!is_null($line)) {
                        $message .= ' near line %s.';
                    } else {
                        $message .= '.';
                    }
                    
                    $message = __($message, $line);
                } else {
                    $message = _($message);
                }
                
                $this->get('iserv.flash')->error($message);
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
     * 
     * @return \Symfony\Component\Form\Form
     */
    private function getImportForm()
    {
        /* @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $this->get('form.factory')->createNamedBuilder('mailalias_import');
        
        $builder
            ->setAction($this->generateUrl('admin_mailalias_import'))
            ->add('file', FileType::class, [
                'label' => false,
                'constraints' => [new NotBlank(['message' => _('Please select a CSV file for import.')])]
            ])
            ->add('enable', BooleanType::class, [
                'label' => false,
                'choices' => [
                    _('Enable new aliases') => 1,
                    _('Disable new aliases') => 0,
                ],
                'constraints' => [new NotBlank(), new Choice(['message' => _('Please select a valid value.'), 'choices' => [1, 0]])]
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Import'),
                'buttonClass' => 'btn-success',
                'icon' => 'pro-file-import'
            ])
        ;
        
        return $builder->getForm();
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
    protected function userAddressLookup($query)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder('u');

        $terms = preg_split("/\s+/", trim($query));
        foreach($terms as $i => $term) {
            $qb
                ->select('u')
                ->from('IServCoreBundle:User', 'u')
                ->andWhere(
                    'LOWER(u.username) LIKE :adra'.$i.' OR LOWER(u.username) LIKE :adr_mail'.$i.' OR ' .
                    'LOWER(u.firstname) LIKE :adra'.$i.' OR LOWER(u.firstname) LIKE :adrb'.$i.' OR ' .
                    'LOWER(u.lastname) LIKE :adra'.$i.' OR LOWER(u.lastname) LIKE :adrb'.$i
                )
                ->setParameter('adra'.$i, strtolower($term).'%')
                ->setParameter('adrb'.$i, '% '.strtolower($term).'%')
                ->setParameter('adr_mail'.$i, '%.'.strtolower($term).'%')
            ;
        }

        return $qb->getQuery()->setMaxResults(10)->getResult();
    }
}
