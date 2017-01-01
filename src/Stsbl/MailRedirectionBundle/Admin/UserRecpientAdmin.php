<?php
// src/Stsbl/MailRedirectionBundle/Admin/UserRecipientAdmin.php
namespace Stsbl\MailRedirectionBundle\Admin;

use IServ\AdminBundle\Admin\AbstractAdmin;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Traits\LoggerTrait;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use Stsbl\MailRedirectionBundle\Security\Privilege;

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
 * Manage which user receives the mails
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class UserRecpientAdmin extends AbstractAdmin
{
    use LoggerTrait;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * {@inheritdoc}
     */
    public function configure() 
    {
        $this->title = _('Users as redirection targets');
        $this->itemTitle = _('User as redirection target');
        $this->id = 'mail_redirection_users';
        $this->routesPrefix = 'admin/mailredirection/users';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-mail-redirection';
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
    public function configureListFields(ListMapper $listMapper) 
    {
        $listMapper->addIdentifier('recipient', null, ['label' => _('User')]);
        $listMapper->add('originalRecipient', null, ['label' => _('Original recipient'), 'responsive' => 'desktop']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureFields(AbstractBaseMapper $mapper) 
    {
        $mapper->add('recipient', null, ['label' => _('User')]);
        $mapper->add('originalRecipient', null, ['label' => _('Original recipient')]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function prepareBreadcrumbs() 
    {
        return [
          _('Mail redirections') => $this->router->generate('admin_mail_redirection_index')  
        ];
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
            return sprintf('%s/%s/%s', $this->routesPrefix, 'batch', 'confirm');
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
    public function postPersist(CrudInterface $object)
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
        $servername = $this->config->get('Servername');
        
        // write log
        $this->log('Benutzer '.$object->getRecipient().' als Ziel für die Weiterleitung '.$object->getOriginalRecipient().'@'.$servername.' hinzugefügt');
    }
    
    /**
     * {@inheritdoc}
     */
    public function postUpdate(CrudInterface $object, array $previousData = null) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
        if ($object->getRecipient() == $previousData['recipient'] &&
            $object->getOriginalRecipient() == $previousData['originalRecipient']) {
            // nothing changed, no log
            return;
        }
        
        $servername = $this->config->get('Servername');
        /* @var $originalRecipient \Stsbl\MailRedirectionBundle\Entity\GroupRecipient */
        $originalRecipient = $this->getObjectManager()->findOneBy('StsblMailRedirectionBundle:Address', ['id' => $previousData['originalRecipient']]);
        /* @var $user \IServ\CoreBundle\Entity\User */
        $user = $this->getObjectManager()->findOneBy('IServCoreBundle:User', ['username' => $previousData['recipient']]);
        
        if ($object->getOriginalRecipient() !== $originalRecipient) {
            // write log
            // use previous group to prevent confusing logs
            $this->log('Originalempfänger '.$originalRecipient.'@'.$servername.' bei Benutzer '.$user.' geändert nach '.$object->getOriginalRecipient().'@'.$servername);
        }
       
        // DO NOT COMPARE WITH $previousData['recipient'], BECAUSE THIS IS A STRING!
        if ($object->getRecipient() !== $user) {
            // write log
            $this->log('Benutzer '.$user.' als Ziel für die Weiterleitung '.$object->getOriginalRecipient().'@'.$servername.' entfernt');
            $this->log('Benutzer '.$object->getRecipient().' als Ziel für die Weiterleitung '.$object->getOriginalRecipient().'@'.$servername.' hinzugefügt');
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $object) 
    {
        /* @var $object \Stsbl\MailRedirectionBundle\Entity\UserRecipient */
        $servername = $this->config->get('Servername');
        
        // write log
        $this->log('Benutzer '.$object->getRecipient().' als Ziel für die Weiterleitung '.$object->getOriginalRecipient().'@'.$servername.' entfernt');
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthorized() 
    {
        return $this->isGranted(Privilege::Admin);
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
