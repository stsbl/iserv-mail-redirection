<?php
// src/Stsbl/MailRedirectionBundle/Admin/UserRecipientAdmin.php
namespace Stsbl\MailRedirectionBundle\Admin;

use IServ\AdminBundle\Admin\AbstractAdmin;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\ListMapper;

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
 * Manage which user receives the mails
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class UserRecpientAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    public function configure() 
    {
        $this->title = _('Users as redirection targets');
        $this->itemTitle = _('User as redirection target');
        $this->id = 'mail_redirection_users';
        $this->routesPrefix = 'admin/mailredirection/users';
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
    public function isAuthorized() 
    {
        return $this->isGranted('PRIV_MAIL_REDIRECTION_ADMIN');
    }
}
