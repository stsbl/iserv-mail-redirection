<?php
// src/Stsbl/MailRedirectionBundle/Admin/MailRedirectionAdmin.php
namespace Stsbl\MailRedirectionBundle\Admin;

use IServ\AdminBundle\Admin\AbstractAdmin;
use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
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
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->title = _('Mail redirections');
        $this->itemTitle = _('Mail redirection');
        $this->id = 'mail_redirection';
        $this->routesPrefix = 'admin/mailredirection';
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper)
    {
        $this->configureAll($listMapper);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureShowFields(ShowMapper $showMapper)
    {
        $this->configureAll($showMapper);
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
    private function configureAll(AbstractBaseMapper $mapper)
    {
        if ($mapper instanceof ListMapper) {
            $mapper->addIdentifier('recipient', null, ['label' => _('Original recipient')]);
        } else {
            $mapper->add('recipient', null, ['label' => _('Original recipient')]);
        }
            
        $mapper->add('enabled', 'boolean', ['label' => _('Enabled')]);
        $mapper->add('comment', null, ['label' => _('Note'), 'responsive' => 'desktop']);
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
        return $this->isGranted('PRIV_MAIL_REDIRECTION_ADMIN');
    }
}
