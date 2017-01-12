<?php
// src/Stsbl/MailRedirectionBundle/Admin/MailRedirectionAdmin.php
namespace Stsbl\MailRedirectionBundle\Admin;

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
use Stsbl\MailRedirectionBundle\Entity\UserRecipient;
use Stsbl\MailRedirectionBundle\Form\Type\GroupRecipientType;
use Stsbl\MailRedirectionBundle\Form\Type\UserRecipientType;
use Stsbl\MailRedirectionBundle\Security\Privilege;
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
        $this->id = 'mail_aliases';
        $this->routesPrefix = 'admin/mailaliases';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-mail-redirection';
        $this->templates['crud_add'] = 'StsblMailRedirectionBundle:Crud:address_add.html.twig';
        $this->templates['crud_edit'] = 'StsblMailRedirectionBundle:Crud:address_edit.html.twig';
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
            'attr' => 
                ['help_text' => _('The local part of the e-mail address which you want to redirect.')]
            ]
        );
        $formMapper->add('userRecipients', BootstrapCollectionType::class, [
            'required' => false,
            'label' => _('Users'),
            'entry_type' => UserRecipientType::class,
            'prototype_name' => 'proto-entry',
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
            'attr' =>
                ['help_text' => _('You can enable or disable this redirection. If it is disabled all assigned users and groups will stop receiving the mails of this address.')]
            ]);
        $formMapper->add('comment', TextareaType::class, [
            'required' => false, 
            'label' => _('Note'), 
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
        
        // explicity block FormMapper
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
        // Overwrite broken route generation of Crud (WHY? =()
        if ('index' === $action) {
            return sprintf('%s', $this->routesPrefix);
        } else if ('add' === $action) {
            return sprintf('%s/%s', $this->routesPrefix, $action);
        } else if ('batch' === $action) {
            return sprintf('%s/%s', $this->routesPrefix, $action);
        } else if ('batch/confirm' === $action) {
            return sprintf('%s%s/%s', $this->routesPrefix, 'batch', 'confirm');
        } else if ('show' === $action) {
            return sprintf('%s/%s/%s', $this->routesPrefix, $action, '{id}');
        } else if ('edit' === $action) {
            return sprintf('%s/%s/%s', $this->routesPrefix, $action, '{id}');
        } else if ('delete' === $action) {
           return sprintf('%s/%s/%s', $this->routesPrefix, $action, '{id}');
        }
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
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
        $userRecipients = $object->getUserRecipients()->toArray();
        $groupRecipients = $object->getGroupRecipients()->toArray();
        $servername = $this->config->get('Servername');
        
        if (is_null($previousData)) {
            // if there is no previous data, assume that we are called from post persist
            foreach ($userRecipients as $recipient) {
                /* @var $recipient \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
                $this->log(sprintf('Benutzer %s als Empfänger von Alias %s@%s hinzugefügt', (string)$recipient->getRecipient(), (string)$object, $servername));
            }
            
            foreach ($groupRecipients as $recipient) {
                /* @var $recipient \Stsbl\MailRedirectionBundle\Entity\GroupRecipient */
                $this->log(sprintf('Gruppe %s als Empfänger von Alias %s@%s hinzugefügt', (string)$recipient->getRecipient(), (string)$object, $servername));
            }
            
            // stop here
            return;
        }
        
        $previousUserRecipients = [];
        foreach ($previousData['userRecipients'] as $recipientId) {
            $previousUserRecipients[] = $this->getObjectManager()->findOneBy('StsblMailRedirectionBundle:UserRecipient', ['id' => $recipientId]);
        }
        
        $previousGroupRecipients = [];
        foreach ($previousData['groupRecipients'] as $recipientId) {
            $previousGroupRecipients[] = $this->getObjectManager()->findOneBy('StsblMailRedirectionBundle:GroupRecipient', ['id' => $recipientId]);
        }
        
        $removedUserRecipients = [];
        
        // array_diff_assoc does not work here, so we use our own optmized version here and in the further comparisons
        // which can better handle arrays with objects inside.
        foreach ($previousUserRecipients as $previousUserRecipient) {
            /* @var $previousUserRecipient \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
            /* @var $userRecipient \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
            $inArray = false;
            
            foreach ($userRecipients as $userRecipient) {
                if ($userRecipient->getRecipient() == $previousUserRecipient->getRecipient()) {
                    $inArray = true;
                }
            }
            
            if (!$inArray) {
                $removedUserRecipients[] = $previousUserRecipient;
            }
        }
        
        $addedUserRecipients = [];
        
        // see above
        foreach ($userRecipients as $userRecipient) {
            /* @var $previousUserRecipient \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
            /* @var $userRecipient \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
            $inArray = false;
            
            foreach ($previousUserRecipients as $previousUserRecipient) {
                if ($previousUserRecipient->getRecipient() == $userRecipient->getRecipient()) {
                    $inArray = true;
                }
            }
            
            if(!$inArray) {
                $addedUserRecipients[] = $userRecipient;
            }
        }
        
        $removedGroupRecipients = [];
        
        // see two above
        foreach ($previousGroupRecipients as $previousGroupRecipient) {
            /* @var $previousGroupRecipient \Stsbl\MailRedirectionBundle\Entity\GroupRecipient */
            /* @var $groupRecipient \Stsbl\MailRedirectionBundle\Entity\GroupRecipient */
            $inArray = false;
            
            foreach ($groupRecipients as $groupRecipient) {
                if ($groupRecipient->getRecipient() == $previousGroupRecipient->getRecipient()) {
                    $inArray = true;
                }
            }
            
            if (!$inArray) {
                $removedGroupRecipients[] = $previousGroupRecipient;
            }
        }
        
        $addedGroupRecipients = [];
        
        // see three above
        foreach ($groupRecipients as $groupRecipient) {
            /* @var $previousGroupRecipient \Stsbl\MailRedirectionBundle\Entity\GroupRecipient */
            /* @var $groupRecipient \Stsbl\MailRedirectionBundle\Entity\GroupRecipient */
            $inArray = false;
            
            foreach ($previousGroupRecipients as $previousGroupRecipient) {
                if ($previousGroupRecipient->getRecipient() == $groupRecipient->getRecipient()) {
                    $inArray = true;
                }
            }
            
            if(!$inArray) {
                $addedGroupRecipients[] = $groupRecipient;
            }
        }
        
        // log removed user recipients
        foreach ($removedUserRecipients as $removed) {
            $this->log(sprintf('Benutzer %s als Empfänger von Alias %s@%s entfernt', (string)$removed->getRecipient(), (string)$object, $servername));
        }
        
        // log added user recipients
        foreach ($addedUserRecipients as $added) {
            $this->log(sprintf('Benutzer %s als Empfänger von Alias %s@%s hinzugefügt', (string)$added->getRecipient(), (string)$object, $servername));
        }
        
        // log removed group recipients
        foreach ($removedGroupRecipients as $removed) {
            $this->log(sprintf('Gruppe %s als Empfänger von Alias %s@%s entfernt', (string)$removed->getRecipient(), (string)$object, $servername));
        }
        
        // log added group recipients
        foreach ($addedGroupRecipients as $added) {
            $this->log(sprintf('Gruppe %s als Empfänger von Alias %s@%s hinzugefügt', (string)$added->getRecipient(), (string)$object, $servername));
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function postPersist(CrudInterface $object) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
        // write log
        $servername = $this->config->get('Servername');
        $this->log(sprintf('Alias %s@%s hinzugefügt', $object->getRecipient(), $servername));
        
        $this->logRecipients($object);
    }
    
    /**
     * Logging should not run as postUpdate, because then we are not able to find previous user recipients!
     * 
     * {@inheritdoc}
     */
    public function preUpdate(CrudInterface $object, array $previousData = null) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
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
                // german grammatic: "Notiz von Alias xy hinzugefügt" sounds ugly.
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
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
        $servername = $this->config->get('Servername');
        
        // write log
        $this->log(sprintf('Alias %s@%s gelöscht', (string)$object, $servername));       
    }
    
    /**
     * Injects config into class, so that we can read servername from it
     * 
     * @param $config Config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
