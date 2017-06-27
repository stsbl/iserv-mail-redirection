<?php
// src/Stsbl/MailAliasBundle/Admin/MailAliasAdmin.php
namespace Stsbl\MailAliasBundle\Admin;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType;
use IServ\AdminBundle\Admin\AbstractAdmin;
use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Traits\LoggerTrait;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use Stsbl\MailAliasBundle\Form\Type\GroupRecipientType;
use Stsbl\MailAliasBundle\Form\Type\UserRecipientType;
use Stsbl\MailAliasBundle\Security\Privilege;
use Stsbl\MailAliasBundle\Util\ArrayUtil;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
class AddressAdmin extends AbstractAdmin
{
    use LoggerTrait;
    
    const LOG_ALIAS_ADDED = 'Alias %s@%s hinzugefügt';
    
    const LOG_USER_RECIPIENT_ADDED = 'Benutzer %s als Empfänger von Alias %s@%s hinzugefügt';
    
    const LOG_GROUP_RECIPIENT_ADDED = 'Gruppe %s als Empfänger von Alias %s@%s hinzugefügt';
    
    /**
     * Gets explanation for import
     * 
     * @return string
     */
    public static function getImportExplanation()
    {
        return _('You can import mail aliases from a CSV file. The CSV file should have no column titles and the following columns (from left to right):');
    }
    
    /**
     * Gets fields for explanation for import
     * 
     * @return array<string>
     */
    public static function getImportExplanationFieldList()
    {
        return [
            _('Original recipient').' '._('(Only local part, without the @ and the domain)'),
            _('Users').' '._('(Account names as a comma separated list, can be empty)'),
            _('Groups').' '. _('(Account names as a comma separated list, can be empty)'),
            _('Note').' ('._('optional').')',
        ];
    }
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->title = _('Mail aliases');
        $this->itemTitle = _('Mail alias');
        $this->id = 'mailalias';
        //$this->routesPrefix = 'admin/mailalias';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-mail-redirection';
        $this->templates['crud_index'] = 'StsblMailAliasBundle:Crud:address_index.html.twig';
        $this->templates['crud_add'] = 'StsblMailAliasBundle:Crud:address_add.html.twig';
        $this->templates['crud_edit'] = 'StsblMailAliasBundle:Crud:address_edit.html.twig';
        $this->templates['crud_multi_edit'] = 'StsblMailAliasBundle:Crud:address_multi_edit.html.twig';
        $this->options['json'] = true;
        $this->options['multi_edit'] = true;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildRoutes() 
    {
        parent::buildRoutes();
        
        $this->routes[self::ACTION_INDEX]['_controller'] = 'StsblMailAliasBundle:MailAlias:index';
        $this->routes[self::ACTION_ADD]['_controller'] = 'StsblMailAliasBundle:MailAlias:add';
        $this->routes[self::ACTION_SHOW]['_controller'] = 'StsblMailAliasBundle:MailAlias:show';
        $this->routes[self::ACTION_EDIT]['_controller'] = 'StsblMailAliasBundle:MailAlias:edit';
    }
    
    /**
     * {@inheritdoc}
     */
    public function __construct($class, $title = null, $itemTitle = null) 
    {
        // set module context for logging
        $this->logModule = 'Mail aliases';
        
        return parent::__construct($class, $title, $itemTitle);
    }

    /**
     * {@inheritdoc}
     */
    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('recipient', null, [
            'label' => _('Original recipient'), 
            'attr' => [
                'help_text' => _('The local part of the e-mail address which you want to redirect.'),
                'input_group' => [
                    'append' => '@'.$this->config->get('Domain')
                ],
            ]
        ]);
        $formMapper->add('userRecipients', BootstrapCollectionType::class, [
            'required' => false,
            'label' => _('Users'),
            'entry_type' => UserRecipientType::class,
            'prototype_name' => 'proto-entry',
            'multi_edit' => true,
            'attr' => [
                'help_text' => _('The users who should receive the e-mails to that address.')
            ],
            // Child options
            'entry_options' => [
                    'attr' => [
                        'widget_col' => 12, // Single child field w/o label col
                    ],
                ],
            ]);
        $formMapper->add('groupRecipients', BootstrapCollectionType::class, [
            'required' => false,
            'label' => _('Groups'),
            'entry_type' => GroupRecipientType::class,
            'prototype_name' => 'proto-entry',
            'multi_edit' => true,
            'attr' => [
                'help_text' => _('The groups which should receive the e-mails to that address.')
            ],
            // Child options
            'entry_options' => [
                    'attr' => [
                        'widget_col' => 12, // Single child field w/o label col
                    ],
                ],
            ]);
        $formMapper->add('enabled', BooleanType::class, [
            'required' => true, 
            'label' => _('Enabled'),
            'multi_edit' => true,
            'attr' =>
                ['help_text' => _('You can enable or disable this redirection. If it is disabled all assigned users and groups will stop receiving the mails of this address.')]
            ]);
        $formMapper->add('comment', TextareaType::class, [
            'required' => false, 
            'label' => _('Note'),
            'multi_edit' => true,
            'attr' =>
                ['help_text' => _('Here you can enter further explanation for this redirection.')]
            ]);
    }
    
    /**
     * Mapper for show fields and form fields
     * 
     * @param AbstractBaseMapper $mapper
     */
    public function configureFields(AbstractBaseMapper $mapper)
    {
        if ($mapper instanceof ListMapper) {
            $mapper->addIdentifier('recipient', null, ['label' => _('Original recipient')]);
        } else if ($mapper instanceof ShowMapper) {
            $mapper->add('recipient', null, ['label' => _('Original recipient')]);
        }
        
        // explicitly block FormMapper
        // the method will also called when building form
        if (!$mapper instanceof FormMapper) {
            $mapper->add('userRecipients', null, ['label' => _('Users')]);
            $mapper->add('groupRecipients', null, ['label' => _('Groups')]);
            $mapper->add('enabled', 'boolean', ['label' => _('Enabled')]);
            $mapper->add('comment', null, ['label' => _('Note'), 'responsive' => 'desktop']);
        }
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRoutePattern($action, $id, $entityBased = true)
    {
        // Overwrite route generation of Crud which struggles with id
        if ('index' === $action) {
            return sprintf('%s%s%s', $this->routesPrefix, $this->id, 'es');
        }

        return parent::getRoutePattern($action, $id, $entityBased);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthorized() 
    {
        return $this->isGranted(Privilege::Admin);
    }
    
    /**
     * Logs the adding and removing of user recipients.
     * 
     * @param CrudInterface $object
     * @param array $previousData
     */
    private function logRecipients(CrudInterface $object, array $previousData = null)
    {
        /* @var $object \Stsbl\MailAliasBundle\Entity\Address */
        $userRecipients = $object->getUserRecipients()->toArray();
        $groupRecipients = $object->getGroupRecipients()->toArray();
        $servername = $this->config->get('Servername');
        
        if (is_null($previousData)) {
            // if there is no previous data, assume that we are called from post persist
            foreach ($userRecipients as $recipient) {
                /* @var $recipient \Stsbl\MailAliasBundle\Entity\UserRecipient */
                $this->log(sprintf(self::LOG_USER_RECIPIENT_ADDED, (string)$recipient->getRecipient(), (string)$object, $servername));
            }
            
            foreach ($groupRecipients as $recipient) {
                /* @var $recipient \Stsbl\MailAliasBundle\Entity\GroupRecipient */
                $this->log(sprintf(self::LOG_GROUP_RECIPIENT_ADDED, (string)$recipient->getRecipient(), (string)$object, $servername));
            }
            
            // stop here
            return;
        }
        
        $previousUserRecipients = [];
        foreach ($previousData['userRecipients'] as $recipientId) {
            $previousUserRecipients[] = $this->getObjectManager()->findOneBy('StsblMailAliasBundle:UserRecipient', ['id' => $recipientId]);
        }
        
        $previousGroupRecipients = [];
        foreach ($previousData['groupRecipients'] as $recipientId) {
            $previousGroupRecipients[] = $this->getObjectManager()->findOneBy('StsblMailAliasBundle:GroupRecipient', ['id' => $recipientId]);
        }
        
        $removedUserRecipients = ArrayUtil::getArrayDifferenceByCallables($previousUserRecipients, $userRecipients, 'getRecipient', 'getRecipient');
        $addedUserRecipients = ArrayUtil::getArrayDifferenceByCallables($userRecipients, $previousUserRecipients, 'getRecipient', 'getRecipient');
        $removedGroupRecipients = ArrayUtil::getArrayDifferenceByCallables($previousGroupRecipients, $groupRecipients, 'getRecipient', 'getRecipient');     
        $addedGroupRecipients = ArrayUtil::getArrayDifferenceByCallables($groupRecipients, $previousGroupRecipients, 'getRecipient', 'getRecipient');
        
        // log removed user recipients
        foreach ($removedUserRecipients as $removed) {
            $this->log(sprintf('Benutzer %s als Empfänger von Alias %s@%s entfernt', (string)$removed->getRecipient(), (string)$object, $servername));
        }
        
        // log added user recipients
        foreach ($addedUserRecipients as $added) {
            $this->log(sprintf(self::LOG_USER_RECIPIENT_ADDED, (string)$added->getRecipient(), (string)$object, $servername));
        }
        
        // log removed group recipients
        foreach ($removedGroupRecipients as $removed) {
            $this->log(sprintf('Gruppe %s als Empfänger von Alias %s@%s entfernt', (string)$removed->getRecipient(), (string)$object, $servername));
        }
        
        // log added group recipients
        foreach ($addedGroupRecipients as $added) {
            $this->log(sprintf(self::LOG_GROUP_RECIPIENT_ADDED, (string)$added->getRecipient(), (string)$object, $servername));
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function postPersist(CrudInterface $object) 
    {
        /* @var $object \Stsbl\MailAliasBundle\Entity\Address */
        // write log
        $servername = $this->config->get('Servername');
        $this->log(sprintf(self::LOG_ALIAS_ADDED, $object->getRecipient(), $servername));
        
        $this->logRecipients($object);
    }
    
    /**
     * Logging should not run as postUpdate, because then we are not able to find previous user recipients!
     * 
     * {@inheritdoc}
     */
    public function preUpdate(CrudInterface $object, array $previousData = null) 
    {
        /* @var $object \Stsbl\MailAliasBundle\Entity\Address */
        if ((string)$object->getRecipient() == (string)$previousData['recipient']
            && (string)$object->getComment() == (string)$previousData['comment'] 
            && (bool)$object->getEnabled() == (bool)$previousData['enabled']) {
            // nothing changed, skip next sections and go directly to recipient log
            goto recipientLog;
        }
        
        $servername = $this->config->get('Servername');
        
        if ((string)$object->getRecipient() !== (string)$previousData['recipient']) {
            // write log
            $this->log(sprintf('Alias %s@%s geändert nach %s@%s', $previousData['recipient'], $servername, (string)$object, $servername));
        }

        if ((bool)$object->getEnabled() !== (bool)$previousData['enabled']) {
            // write log
            if ($object->getEnabled()) {
                $text = 'aktiviert';
            } else {
                $text = 'deaktiviert';
            }
            
            // write log*
            $this->log(sprintf('Alias %s@%s %s', (string)$object, $servername, $text));
        }
        
        if((string)$object->getComment() !== (string)$previousData['comment']) {
            $prePosition = 'von';
            if(empty($object->getComment())) {
                $text = 'gelöscht';
            } else if (empty($previousData['comment'])) {
                // german grammar: "Notiz von Alias xy hinzugefügt" sounds ugly.
                $prePosition = 'für';
                $text = 'hinzugefügt';
            } else {
                $text = 'geändert';
            }
            
            // write log
            $this->log(sprintf('Notiz %s Alias %s@%s %s', $prePosition, (string)$object, $servername, $text));
        }
        
        recipientLog:
        // log recipient changes
        $this->logRecipients($object, $previousData);
    }
    
    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $object) 
    {
        /* @var $object \Stsbl\MailAliasBundle\Entity\Address */
        $servername = $this->config->get('Servername');
        
        // write log
        $this->log(sprintf('Alias %s@%s gelöscht', (string)$object, $servername));       
    }
    
    /**
     * Injects config into class, so that we can read servername from it
     * 
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
