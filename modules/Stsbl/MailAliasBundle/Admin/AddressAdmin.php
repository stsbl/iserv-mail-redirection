<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Admin;

use IServ\AdminBundle\Admin\AdminServiceCrud;
use IServ\BootstrapBundle\Form\Type\BootstrapCollectionType;
use IServ\Bundle\Form\Form\Type\BooleanType;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\Specification\PropertyMatchSpecification;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\Logger;
use IServ\CoreBundle\Traits\LoggerTrait;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use IServ\CrudBundle\Routing\RoutingDefinition;
use IServ\CrudBundle\Table\Filter\FilterGroup;
use IServ\CrudBundle\Table\Filter\ListSearchFilter;
use IServ\CrudBundle\Table\Filter\ListSpecificationFilter;
use IServ\CrudBundle\Table\ListHandler;
use IServ\Library\Config\Config;
use Stsbl\MailAliasBundle\Admin\Filter\AliasAssociationSpecification;
use Stsbl\MailAliasBundle\Controller\MailAliasController;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Form\Type\GroupRecipientType;
use Stsbl\MailAliasBundle\Form\Type\UserRecipientType;
use Stsbl\MailAliasBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

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
final class AddressAdmin extends AdminServiceCrud
{
    use LoggerTrait;

    public const LOG_ALIAS_ADDED = 'Alias %s@%s hinzugefügt';

    public const LOG_USER_RECIPIENT_ADDED = 'Benutzer %s als Empfänger von Alias %s@%s hinzugefügt';

    public const LOG_GROUP_RECIPIENT_ADDED = 'Gruppe %s als Empfänger von Alias %s@%s hinzugefügt';

    /**
     * {@inheritDoc}
     */
    protected static $entityClass = Address::class;

    /**
     * Gets explanation for import
     */
    public static function getImportExplanation(): string
    {
        return _('You can import mail aliases from a CSV file. The CSV file should have no column titles and the ' .
            'following columns (from left to right):');
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
        $this->logModule = 'Mail aliases';

        $this->title = _('Mail aliases');
        $this->itemTitle = _('Mail alias');
        $this->id = 'mailalias';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-mail-redirection';
        $this->templates['crud_index'] = '@StsblMailAlias/Crud/address_index.html.twig';
        $this->templates['crud_add'] = '@StsblMailAlias/Crud/address_add.html.twig';
        $this->templates['crud_edit'] = '@StsblMailAlias/Crud/address_edit.html.twig';
        $this->templates['crud_multi_edit'] = '@StsblMailAlias/Crud/address_multi_edit.html.twig';
        $this->templates['crud_show'] = '@StsblMailAlias/Crud/address_show.html.twig';
        $this->options['json'] = true;
        $this->options['multi_edit'] = true;
    }

    /**
     * {@inheritDoc}
     */
    public static function defineRoutes(): RoutingDefinition
    {
        return parent::createRoutes('mailalias', 'mailalias')
            ->useControllerForAction(self::ACTION_INDEX, MailAliasController::class . '::indexAction')
            ->useControllerForAction(self::ACTION_ADD, MailAliasController::class . '::addAction')
            ->useControllerForAction(self::ACTION_SHOW, MailAliasController::class . '::showAction')
            ->useControllerForAction(self::ACTION_EDIT, MailAliasController::class . '::editAction')
            ->setNamePrefix('admin_')
            ->setPathPrefix('/admin/')
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
                        'append' => '@' . $this->config()->get('Domain')
                    ],
                ]
            ])
            ->add('users', BootstrapCollectionType::class, [
                'required' => false,
                'label' => _('Users'),
                'multi_edit' => true,
                'entry_type' => UserRecipientType::class,
                'prototype_name' => 'proto-entry',
                'attr' => [
                    'help_text' => _('The users who should receive the e-mails to that address.'),
                ],
                // Child options
                'entry_options' => [
                    'attr' => [
                        'widget_col' => 12, // Single child field w/o label col
                    ],
                ],
            ])
            ->add('groups', BootstrapCollectionType::class, [
                'required' => false,
                'label' => _('Groups'),
                'multi_edit' => true,
                'entry_type' => GroupRecipientType::class,
                'prototype_name' => 'proto-entry',
                'attr' => [
                    'help_text' => _('The groups which should receive the e-mails to that address.'),
                ],
                // Child options
                'entry_options' => [
                    'attr' => [
                        'widget_col' => 12, // Single child field w/o label col
                    ],
                ],
            ])
            ->add('enabled', BooleanType::class, [
                'required' => true,
                'label' => _('Enabled'),
                'multi_edit' => true,
                'attr' => [
                    'help_text' => _('You can enable or disable this redirection. If it is disabled all assigned ' .
                        'users and groups will stop receiving the mails of this address.'),
                ]
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => _('Note'),
                'multi_edit' => true,
                'attr' => [
                    'help_text' => _('Here you can enter further explanation for this redirection.'),
                ]
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureFields(AbstractBaseMapper $mapper): void
    {
        if ($mapper instanceof ListMapper) {
            $mapper->addIdentifier('recipient', null, [
                'label' => _('Original recipient'),
                'template' => '@StsblMailAlias/List/field_recipient.html.twig',
            ]);
        } elseif ($mapper instanceof ShowMapper) {
            $mapper->add('recipient', null, [
                'label' => _('Original recipient'),
                'template' => '@StsblMailAlias/Show/field_recipient.html.twig',
            ]);
        }

        // explicitly block FormMapper
        // the method will also called when building form
        if (!$mapper instanceof FormMapper) {
            $mapper->add('users', null, ['label' => _('Users')]);
            $mapper->add('groups', null, ['label' => _('Groups')]);
            $mapper->add('enabled', 'boolean', ['label' => _('Enabled')]);
            $mapper->add('comment', null, ['label' => _('Note'), 'responsive' => 'desktop']);
        }
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
        $enabledFilterGroup->addListFilter((new ListSpecificationFilter(_('Yes'), new PropertyMatchSpecification('enabled', true)))->setName('true'));
        $enabledFilterGroup->addListFilter((new ListSpecificationFilter(_('No'), new PropertyMatchSpecification('enabled', false)))->setName('false'));

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
        $userRecipients = $object->getUsers()->toArray();
        $groupRecipients = $object->getGroups()->toArray();
        $servername = $this->config()->get('Domain');

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
        foreach ($previousData['users'] as $recipientId) {
            $previousUserRecipients[] = $this->getObjectManager()->findOneBy(User::class, ['username' => $recipientId]);
        }

        $previousGroupRecipients = [];
        foreach ($previousData['groups'] as $recipientId) {
            $previousGroupRecipients[] = $this->getObjectManager()->findOneBy(Group::class, ['account' => $recipientId]);
        }

        $removedUserRecipients = array_diff($previousUserRecipients, $userRecipients);
        $addedUserRecipients = array_diff($userRecipients, $previousUserRecipients);
        $removedGroupRecipients = array_diff($previousGroupRecipients, $groupRecipients);
        $addedGroupRecipients = array_diff($groupRecipients, $previousGroupRecipients);

        // log removed user recipients
        foreach ($removedUserRecipients as $removed) {
            $this->log(sprintf('Benutzer %s als Empfänger von Alias %s@%s entfernt', (string)$removed, (string)$object, $servername));
        }

        // log added user recipients
        foreach ($addedUserRecipients as $added) {
            $this->log(sprintf(self::LOG_USER_RECIPIENT_ADDED, (string)$added, (string)$object, $servername));
        }

        // log removed group recipients
        foreach ($removedGroupRecipients as $removed) {
            $this->log(sprintf('Gruppe %s als Empfänger von Alias %s@%s entfernt', (string)$removed, (string)$object, $servername));
        }

        // log added group recipients
        foreach ($addedGroupRecipients as $added) {
            $this->log(sprintf(self::LOG_GROUP_RECIPIENT_ADDED, (string)$added, (string)$object, $servername));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(CrudInterface $object): void
    {
        /* @var $object Address */
        // write log
        $servername = $this->config()->get('Domain');
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
        if ($object->getRecipient() === $previousData['recipient'] &&
            $object->getComment() === $previousData['comment'] &&
            $object->getEnabled() === $previousData['enabled']) {
            // if nothing is changed, skip next sections and go directly to recipient log
        } else {
            $servername = $this->config()->get('Domain');

            if ($object->getRecipient() !== $previousData['recipient']) {
                // write log
                $this->log(sprintf(
                    'Alias %s@%s geändert nach %s@%s',
                    $previousData['recipient'],
                    $servername,
                    $object,
                    $servername
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
                $this->log(sprintf('Notiz %s Alias %s@%s %s', $prePosition, (string)$object, $servername, $text));
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
        $servername = $this->config()->get('Domain');

        // write log
        $this->log(sprintf('Alias %s@%s gelöscht', (string)$object, $servername));
    }

    /**
     * @required
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     *
     * @required
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    private function config(): Config
    {
        return $this->locator->get(Config::class);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        $deps = parent::getSubscribedServices();
        $deps[] = Config::class;

        return $deps;
    }
}
