<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Admin;

use IServ\Bundle\AdminIntegration\Menu\AdminBreadcrumbsInterface;
use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Crud\Command\Request\CrudCommand;
use IServ\CrudBundle\Crud\Response\Asset\Asset;
use IServ\Bundle\Form\Form\Type\BooleanType;
use IServ\Bundle\Autocomplete\Domain\AutocompleteType;
use IServ\Bundle\Autocomplete\Form\Type\AutocompleteTagsType;
use IServ\Bundle\AdminLog\Logger\AdminLoggerInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use IServ\CrudBundle\Routing\RoutingDefinition;
use IServ\CrudBundle\Table\Filter\FilterGroup;
use IServ\CrudBundle\Table\Filter\ListSearchFilter;
use IServ\CrudBundle\Table\Filter\ListSpecificationFilter;
use IServ\CrudBundle\Table\ListHandler;
use Stsbl\IServ\MailRedirection\Config\IServConfig;
use Stsbl\IServ\MailRedirection\Admin\Filter\AliasAssociationSpecification;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
 * CRUD to manage mail aliases via IDesk
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://mit.otg/licenses/MIT>
 */
final class AddressAdmin extends ServiceCrud
{
    public const LOG_ALIAS_ADDED = 'Alias %s@%s hinzugefügt';

    public const LOG_USER_RECIPIENT_ADDED = 'Benutzer %s als Empfänger von Alias %s@%s hinzugefügt';

    public const LOG_GROUP_RECIPIENT_ADDED = 'Gruppe %s als Empfänger von Alias %s@%s hinzugefügt';

    /**
     * {@inheritDoc}
     */
    protected static $entityClass = Address::class;

    /** Use the v4 command handlers instead of the deprecated controller bridge. */
    protected static $useCommands = true;

    /**
     * Gets explanation for import
     */
    public static function getImportExplanation(): string
    {
        return _('You can import mail aliases from a CSV file. The CSV file should have no column titles and the '
            . 'following columns (from left to right):');
    }

    /**
     * Gets fields for explanation for import
     *
     * @return string[]
     */
    public static function getImportExplanationFieldList(): array
    {
        return [
            _('Original recipient') . ' ' . _('(Only local part, without the @ and the domain)'),
            _('Users') . ' ' . _('(Account names as a comma separated list, can be empty)'),
            _('Groups') . ' ' . _('(Account names as a comma separated list, can be empty)'),
            _('Note') . ' (' . _('optional') . ')',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        // set module context for logging

        $this->title = _('Mail aliases');
        $this->itemTitle = _('Mail alias');
        $this->id = 'mailalias';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-mail-redirection';
        $this->templates['crud_index'] = 'Crud/address_index.html.twig';
        $this->templates['crud_add'] = 'Crud/address_add.html.twig';
        $this->templates['crud_edit'] = 'Crud/address_edit.html.twig';
        $this->templates['crud_multi_edit'] = 'Crud/address_multi_edit.html.twig';
        $this->templates['crud_show'] = 'Crud/address_show.html.twig';
        $this->options['legacy_assets'] = false;
        $this->options['multi_edit'] = true;
        $this->options['use_admin_integration_bundle'] = true;
        $this->options['secure_index'] = true;
    }

    /** @return list<\IServ\Library\Breadcrumb\Breadcrumb> */
    public function prepareBreadcrumbs(): array
    {
        return [$this->adminBreadcrumbs()->root()];
    }

    /** @return list<Asset> */
    public function getAssetsForCommand(CrudCommand $command): array
    {
        return [
            ...parent::getAssetsForCommand($command),
            // AutocompleteTagsType only supplies data attributes. Its renderer
            // is provided by this separate bundle asset.
            Asset::css('js/autocomplete.css', 'iserv-autocomplete'),
            Asset::js('js/autocomplete.js', 'iserv-autocomplete'),
            Asset::css('css/recipients.css'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function defineRoutes(): RoutingDefinition
    {
        return self::createRoutes('mailalias', 'mailalias')
            ->setNamePrefix('admin_')
            ->setPathPrefix('admin/')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('recipient', null, [
                'label' => _('Original recipient'),
                'attr' => [
                    'help_text' => _('The local part of the e-mail address which you want to redirect.'),
                    'input_group' => [
                        'append' => '@' . $this->iservConfig()->domain(),
                    ],
                ],
            ])
            ->add('recipients', AutocompleteTagsType::class, [
                'required' => false,
                'label' => _('Recipients'),
                'multi_edit' => true,
                'autocomplete_types' => [AutocompleteType::USER, AutocompleteType::GROUP],
                'multiple' => true,
                'tag_source' => $this->router()->generate('admin_mailalias_recipients', ['type' => 'user,group']),
                'attr' => [
                    'help_text' => _('The users and groups who should receive the e-mails to that address.'),
                ],
            ])
            ->add('enabled', BooleanType::class, [
                'required' => true,
                'label' => _('Enabled'),
                'multi_edit' => true,
                'attr' => [
                    'help_text' => _('You can enable or disable this redirection. If it is disabled all assigned '
                        . 'users and groups will stop receiving the mails of this address.'),
                ],
            ])
            ->add('displayName', TextType::class, [
                'required' => false,
                'label' => _('Display name'),
                'multi_edit' => true,
                'attr' => [
                    'help_text' => _('The public name displayed for this alias in IServ autocomplete. Leave empty to display the e-mail address.'),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => _('Note'),
                'multi_edit' => true,
                'attr' => [
                    'help_text' => _('Here you can enter further explanation for this redirection.'),
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('recipient', null, [
                'label' => _('Original recipient'),
                'template' => 'List/field_recipient.html.twig',
            ])
            ->add('recipients', null, [
                'label' => _('Recipients'),
                'template' => 'List/field_recipients.html.twig',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('recipient', null, [
                'label' => _('Original recipient'),
                'template' => 'Show/field_recipient.html.twig',
            ])
            ->add('recipients', null, [
                'label' => _('Recipients'),
                'template' => 'Show/field_recipients.html.twig',
            ])
            ->add('enabled', 'boolean', ['label' => _('Enabled')])
            ->add('displayName', null, ['label' => _('Display name')])
            ->add('comment', null, ['label' => _('Note'), 'responsive' => 'desktop']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFilter(ListHandler $listHandler): void
    {
        $associationFilterGroup = new FilterGroup('associations', _('All aliases'));
        $associationFilterGroup->addListFilter((new ListSpecificationFilter(_('Aliases without associated users and groups'), new AliasAssociationSpecification(true, true)))->setName('without'));
        $associationFilterGroup->addListFilter((new ListSpecificationFilter(_('Aliases without associated users'), new AliasAssociationSpecification(false, true)))->setName('without-users'));
        $associationFilterGroup->addListFilter((new ListSpecificationFilter(_('Aliases without associated groups'), new AliasAssociationSpecification(true, false)))->setName('without-groups'));

        $listHandler->addListFilterGroup($associationFilterGroup);

        $enabledFilterGroup = new FilterGroup('enabled', _('Enabled'));
        $enabledFilterGroup->addListFilter((new ListSpecificationFilter(_('Yes'), new \Stsbl\IServ\MailRedirection\Admin\Filter\PropertyMatchSpecification('enabled', true)))->setName('true'));
        $enabledFilterGroup->addListFilter((new ListSpecificationFilter(_('No'), new \Stsbl\IServ\MailRedirection\Admin\Filter\PropertyMatchSpecification('enabled', false)))->setName('false'));

        $listHandler->addListFilterGroup($enabledFilterGroup);

        $listHandler->addListFilter((new ListSearchFilter(_('Recipient'), ['recipient']))->setName('recipient'));
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized(): bool
    {
        return $this->isGranted(Privilege::ADMIN);
    }

    /**
     * Logs the adding and removing of user recipients.
     *
     * @param mixed[] $previousData
     */
    private function logRecipients(CrudInterface $object, array $previousData = null): void
    {
        /* @var $object Address */
        $userRecipients = array_map(static fn($recipient): string => (string) $recipient->getUsername(), $object->getUsers()->toArray());
        $groupRecipients = array_map(static fn($recipient): string => (string) $recipient->getAccount(), $object->getGroups()->toArray());
        $servername = $this->iservConfig()->domain();

        if (null === $previousData) {
            // if there is no previous data, assume that we are called from post persist
            foreach ($userRecipients as $recipient) {
                $this->log(sprintf(self::LOG_USER_RECIPIENT_ADDED, $recipient, $object, $servername));
            }

            foreach ($groupRecipients as $recipient) {
                $this->log(sprintf(self::LOG_GROUP_RECIPIENT_ADDED, $recipient, $object, $servername));
            }

            // stop here
            return;
        }

        $previousUserRecipients = [];
        $previousGroupRecipients = [];
        foreach ($previousData['recipients'] ?? [] as $recipient) {
            if ($recipient instanceof \IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData && $recipient->getId() !== null) {
                if ($recipient->getSource() === 'user') {
                    $previousUserRecipients[] = $recipient->getId();
                } elseif ($recipient->getSource() === 'group') {
                    $previousGroupRecipients[] = $recipient->getId();
                }
            }
        }

        $removedUserRecipients = array_diff($previousUserRecipients, $userRecipients);
        $addedUserRecipients = array_diff($userRecipients, $previousUserRecipients);
        $removedGroupRecipients = array_diff($previousGroupRecipients, $groupRecipients);
        $addedGroupRecipients = array_diff($groupRecipients, $previousGroupRecipients);

        // log removed user recipients
        foreach ($removedUserRecipients as $removed) {
            $this->log(sprintf('Benutzer %s als Empfänger von Alias %s@%s entfernt', (string) $removed, (string) $object, $servername));
        }

        // log added user recipients
        foreach ($addedUserRecipients as $added) {
            $this->log(sprintf(self::LOG_USER_RECIPIENT_ADDED, (string) $added, (string) $object, $servername));
        }

        // log removed group recipients
        foreach ($removedGroupRecipients as $removed) {
            $this->log(sprintf('Gruppe %s als Empfänger von Alias %s@%s entfernt', (string) $removed, (string) $object, $servername));
        }

        // log added group recipients
        foreach ($addedGroupRecipients as $added) {
            $this->log(sprintf(self::LOG_GROUP_RECIPIENT_ADDED, (string) $added, (string) $object, $servername));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(CrudInterface $object): void
    {
        /* @var $object Address */
        // write log
        $servername = $this->iservConfig()->domain();
        $this->log(sprintf(self::LOG_ALIAS_ADDED, $object->getRecipient(), $servername));

        $this->logRecipients($object);
    }

    /**
     * Logging should not run as postUpdate, because then we are not able to find previous user recipients!
     *
     * {@inheritdoc}
     */
    public function preUpdate(CrudInterface $object, array $previousData = null): void
    {
        /* @var $object Address */
        if ($object->getRecipient() === $previousData['recipient']
            && $object->getComment() === $previousData['comment']
            && $object->getEnabled() === $previousData['enabled']) {
            // if nothing is changed, skip next sections and go directly to recipient log
        } else {
            $servername = $this->iservConfig()->domain();

            if ($object->getRecipient() !== $previousData['recipient']) {
                // write log
                $this->log(sprintf(
                    'Alias %s@%s geändert nach %s@%s',
                    $previousData['recipient'],
                    $servername,
                    $object,
                    $servername,
                ));
            }

            if ($object->getEnabled() !== $previousData['enabled']) {
                // write log
                if ($object->getEnabled()) {
                    $text = 'aktiviert';
                } else {
                    $text = 'deaktiviert';
                }

                // write log*
                $this->log(sprintf('Alias %s@%s %s', $object, $servername, $text));
            }

            if ($object->getComment() !== $previousData['comment']) {
                $prePosition = 'von';
                if (strlen($object->getComment() ?? '') === 0) {
                    $text = 'gelöscht';
                } elseif (strlen($previousData['comment'] ?? '') !== 0) {
                    // german grammar: "Notiz von Alias xy hinzugefügt" sounds ugly.
                    $prePosition = 'für';
                    $text = 'hinzugefügt';
                } else {
                    $text = 'geändert';
                }

                // write log
                $this->log(sprintf('Notiz %s Alias %s@%s %s', $prePosition, (string) $object, $servername, $text));
            }

        }

        // log recipient changes
        $this->logRecipients($object, $previousData);
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $object): void
    {
        /* @var $object Address */
        $servername = $this->iservConfig()->domain();

        // write log
        $this->log(sprintf('Alias %s@%s gelöscht', (string) $object, $servername));
    }

    /**
     * {@inheritDoc}
     *
     * @required
     */
    public function setLogger(AdminLoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private AdminLoggerInterface $logger;

    private function log(string $message): void
    {
        $this->logger->writeForModule($message, 'Mail aliases');
    }

    private function iservConfig(): IServConfig
    {
        return $this->locator->get(IServConfig::class);
    }

    private function adminBreadcrumbs(): AdminBreadcrumbsInterface
    {
        return $this->locator->get(AdminBreadcrumbsInterface::class);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        $deps = parent::getSubscribedServices();
        $deps[] = IServConfig::class;
        $deps[] = AdminBreadcrumbsInterface::class;

        return $deps;
    }
}
