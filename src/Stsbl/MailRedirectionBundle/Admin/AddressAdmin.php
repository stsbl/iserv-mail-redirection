<?php
// src/Stsbl/MailRedirectionBundle/Admin/MailRedirectionAdmin.php
namespace Stsbl\MailRedirectionBundle\Admin;

use IServ\AdminBundle\Admin\AbstractAdmin;
use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Traits\LoggerTrait;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use Stsbl\MailRedirectionBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/*
 * The MIT License
 *
 * Copyright 2016 Felix Jacobi.
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
 * CRUD to manage mail redirections via IDesk
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
        $this->title = _('Mail redirections');
        $this->itemTitle = _('Mail redirection');
        $this->id = 'mail_redirection';
        $this->routesPrefix = 'admin/mailredirection';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-mail-redirection';
        $this->templates['crud_index'] = 'StsblMailRedirectionBundle:Crud:address_index.html.twig';
    }
    
    /**
     * {@inheritdoc}
     */
    public function __construct($class, $title = null, $itemTitle = null) 
    {
        // set module context for logging
        $this->logModule = _('Mail redirection');
        
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
            $mapper->add('enabled', 'boolean', ['label' => _('Enabled')]);
            $mapper->add('comment', null, ['label' => _('Note'), 'responsive' => 'desktop']);
            $mapper->add('userRecipients', null, ['label' => _('Users')]);
            $mapper->add('groupRecipients', null, ['label' => _('Groups')]);
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
    public function getIndexActions() 
    {
        $links = parent::getIndexActions();
        
        $links['users'] = [$this->getRouter()->generate('admin_mail_redirection_users_index'), _('Set up user as redirection target'), 'pro-user'];
        $links['groups'] = [$this->getRouter()->generate('admin_mail_redirection_groups_index'), _('Set up groups as redirection target'), 'pro-group'];
        
        return $links;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthorized() 
    {
        return $this->isGranted(Privilege::Admin);
    }
    
    /**
     * {@inheritdoc}
     */
    public function postPersist(CrudInterface $object) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
        // write log
        $servername = $this->config->get('Servername');
        $this->log('Weiterleitung für Adresse '.$object->getRecipient().'@'.$servername.' hinzugefügt');
    }
    
    /**
     * {@inheritdoc}
     */
    public function postUpdate(CrudInterface $object, array $previousData = null) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
        if ($object->getRecipient() == $previousData['recipient']
            && $object->getComment() == $previousData['comment'] 
            && $object->getEnabled() == $previousData['enabled']) {
            // nothing changed, no log
            return;
        }
        
        $servername = $this->config->get('Servername');
        
        if ($object->getRecipient() !== $previousData['recipient']) {
            // write log
            $this->log('Weiterleitung für Adresse '.$previousData['recipient'].'@'.$servername.' geändert nach '.$object->getRecipient().'@'.$servername);
        }

        if ($object->getEnabled() !== $previousData['enabled']) {
            // write log
            if ($object->getEnabled()) {
                $text = 'aktiviert';
            } else {
                $text = 'deaktviert';
            }
            
            // write log
            $this->log('Weiterleitung für Adresse '.$object->getRecipient().'@'.$servername.' '.$text);
        }
        
        if($object->getComment() !== $previousData['comment']) {
            if(empty($object->getComment())) {
                $text = 'gelöscht';
            } else if (empty($previousData['comment'])) {
                $text = 'hinzugefügt';
            } else {
                $text = 'geändert';
            }
            
            // write log
            $this->log('Kommentar der Weiterleitung für Adresse '.$object->getRecipient().'@'.$servername.' '.$text);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $object) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\Address */
        $servername = $this->config->get('Servername');
        
        // write log
        $this->log('Weiterleitung für Adresse '.$object->getRecipient().'@'.$servername.' gelöscht');       
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
