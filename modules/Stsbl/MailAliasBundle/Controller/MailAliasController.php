<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Controller;

use IServ\Bundle\Flash\Flash\FlashInterface;
use IServ\Bundle\Flash\Flash\FlashMessage;
use IServ\Bundle\AdminLog\Logger\AdminLoggerInterface;
use IServ\CrudBundle\Controller\StrictCrudController;
use IServ\Library\Breadcrumb\Breadcrumb;
use IServ\Library\Breadcrumb\BreadcrumbManagerInterface;
use IServ\Library\Config\Config;
use IServ\Library\Avatar\AvatarSize;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Avatar\Renderer\AvatarRenderStyle;
use IServ\Library\Avatar\Renderer\Exception\AvatarRendererException;
use IServ\Library\Avatar\UrlGenerator\AvatarPlaceholderStyle;
use IServ\Library\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\MailAliasBundle\Admin\AddressAdmin;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Stsbl\MailAliasBundle\Form\Type\ImportType;
use Stsbl\MailAliasBundle\Model\Import;
use Stsbl\MailAliasBundle\Repository\AddressRepository;
use Stsbl\MailAliasBundle\Service\Importer;
use Stsbl\MailAliasBundle\Service\IdmRecipientLookup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * Public mail-alias source for the IServ autocomplete module.
     *
     * @Route("/mailalias/autocomplete/api", name="mailalias_autocomplete_api", methods={"GET"})
     */
    public function autocompleteApiAction(Request $request, AddressRepository $addresses, Config $config): JsonResponse
    {
        $query = $request->query->get('query') ?? $request->query->get('values');
        if (!is_string($query) || trim($query) === '') {
            return new JsonResponse([]);
        }

        $suggestions = array_map(static function (Address $address) use ($config): array {
            $mail = $address->getRecipient() . '@' . $config->get('Domain');

            return [
                'label' => $address->getDisplayName() ?: $mail,
                'text' => $address->getDisplayName() ?: $mail,
                'value' => 'personal:' . $mail,
                'source' => 'personal',
                'avatar' => null,
                'avatarHtml' => null,
                'icon' => 'fa-envelope',
                'extra' => $address->getDisplayName() ? $mail : _('Mail alias'),
                'certainty' => 5,
                'fuzzy' => false,
                'expandable' => false,
                'readonly' => false,
            ];
        }, $addresses->findEnabledByRecipientQuery($query));

        return $request->query->has('values') ? new JsonResponse(['data' => $suggestions]) : new JsonResponse($suggestions);
    }

    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request): array|Response
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
                $session->remove('mailalias_import_warnings');
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
    public function getRecipientsAutocompleteAction(
        Request $request,
        IdmRecipientLookup $idmRecipientLookup,
        AvatarRendererInterface $avatarRenderer,
    ): JsonResponse {
        $type = $request->query->get('type');
        $query = $request->query->get('query');
        $suggestions = [];

        if ($type === null) {
            throw new \InvalidArgumentException('Parameter type should not be null.');
        }
        $types = explode(',', $type);
        if (array_diff($types, ['group', 'user'])) {
            throw new \InvalidArgumentException(sprintf('Invalid type %s.', $type));
        }

        $query = is_string($query) ? $query : '';
        if (in_array('group', $types, true)) {
            $result = $idmRecipientLookup->groups($query);
            foreach (['exact' => 10, 'partial' => 5, 'fuzzy' => 1] as $bucket => $certainty) {
                foreach ($result[$bucket] ?? [] as $group) {
                $account = $group['group'] ?? null;
                if (!is_string($account) || $account === '') {
                    continue;
                }
                $suggestions[] = [
                    'label' => $group['name'] ?? $account,
                    'text' => $group['name'] ?? $account,
                    'value' => 'group:' . $account,
                    'source' => 'group',
                    'avatarHtml' => $this->renderGroupAvatar($avatarRenderer, (string) ($group['name'] ?? $account)),
                    'icon' => 'fa-users',
                    'extra' => _('Group'),
                    'certainty' => $certainty,
                    'fuzzy' => $bucket === 'fuzzy',
                    'expandable' => false,
                    'readonly' => false,
                ];
                }
            }
        }
        if (in_array('user', $types, true)) {
            $result = $idmRecipientLookup->users($query);
            foreach (['exact' => 10, 'partial' => 5, 'fuzzy' => 1] as $bucket => $certainty) {
                foreach ($result[$bucket] ?? [] as $user) {
                $account = $user['user'] ?? null;
                if (!is_string($account) || $account === '') {
                    continue;
                }
                $label = trim(sprintf('%s %s', $user['firstname'] ?? '', $user['lastname'] ?? ''));
                $suggestions[] = [
                    'label' => $label === '' ? $account : $label,
                    'text' => $label === '' ? $account : $label,
                    'value' => 'user:' . $account,
                    'source' => 'user',
                    'avatarHtml' => $this->renderUserAvatar($avatarRenderer, $user['hexUuid'] ?? null),
                    'icon' => 'fa-user',
                    'extra' => $user['auxInfo'] ?? _('User'),
                    'certainty' => $certainty,
                    'fuzzy' => $bucket === 'fuzzy',
                    'expandable' => false,
                    'readonly' => false,
                ];
                }
            }
        }

        return new JsonResponse($suggestions);
    }

    private function renderUserAvatar(AvatarRendererInterface $avatarRenderer, mixed $uuid): ?string
    {
        if (!is_string($uuid) || $uuid === '') {
            return null;
        }

        try {
            return $avatarRenderer->render(Uuid::createFromNormalized($uuid), AvatarSize::default());
        } catch (\InvalidArgumentException|AvatarRendererException) {
            return null;
        }
    }

    private function renderGroupAvatar(AvatarRendererInterface $avatarRenderer, string $name): ?string
    {
        try {
            return $avatarRenderer->renderPlaceholder(
                $name,
                AvatarSize::default(),
                AvatarRenderStyle::ROUNDED,
                AvatarPlaceholderStyle::GROUP,
            );
        } catch (AvatarRendererException) {
            return null;
        }
    }

    /**
     * Imports a submitted csv file
     *
     * @Route("admin/mailalias/import", name="admin_mailalias_import")
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
     * @Template()
     */
    public function importAction(
        Importer $importer,
        AdminLoggerInterface $logger,
        Request $request,
        Config $config,
        FlashInterface $flash,
        BreadcrumbManagerInterface $breadcrumbManager,
    ): RedirectResponse|array {
        $session = $this->getSession();

        $form = $this->createImportForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Import $import */
            $import = $form->getData();

            try {
                $result = $importer->import($import);

                $warnings = $result->getWarnings();

                if (!empty($warnings)) {
                    $session->set('mailalias_import_warnings', implode("\n", $warnings));
                }

                $servername = $config->getString('Domain');
                $module = 'Mail aliases';
                $messages = [];

                $newAddresses = $result->getNewAddresses();
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

                $flash->add(new FlashMessage('error', $message));
            }
        }

        // track path
        $breadcrumbManager->add(Breadcrumb::create(_('Mail aliases'), $this->generateUrl('admin_mailalias_index')));
        $breadcrumbManager->add(Breadcrumb::create(_('Import')));

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

}
