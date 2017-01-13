<?php
// src/Stsbl/MailRedirectionBundle/Controler/MailAliasController.php
namespace Stsbl\MailRedirectionBundle\Controller;

use Doctrine\ORM\NoResultException;
use IServ\CoreBundle\Controller\PageController;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
 * Backend controller for Mail Alias Management
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
 * @Route("admin/mailaliases")
 */
class MailAliasController extends PageController 
{
    /**
     * Get auto-completion suggestions for users and groups
     * 
     * @Method("GET")
     * @Route("/recipients", name="admin_mail_aliases_recipients", options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecipientsAutocomplete(Request $request)
    {
        $type = $request->query->get('type');
        $query = $request->query->get('query');
        $explodedQuery = explode(' ', $query);
        
        if (is_null($type)) {
            throw new \InvalidArgumentException('Parameter type should not be null.');
        }
        if ($type !== 'group' && $type !== 'user') {
            throw new \InvalidArgumentException(sprintf('Invalid type %s.', $type));
        }
        
        $suggestions = [];
        if (empty($query)) {
            if ($type == 'group') {
                $results = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
            } else {
                $results = $this->getDoctrine()->getRepository('IServCoreBundle:User')->findAll();
            }
        } else {
            if ($type == 'group') {
                /* @var $qb \Doctrine\ORM\QueryBuilder */
                $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                
                $qb
                    ->select('p')
                    ->from('IServCoreBundle:Group', 'p')
                    ->where('p.name LIKE :query')
                    ->setParameter('query', '%'.$query.'%')
                ;
                
                try {
                    $results = $qb->getQuery()->getResult();
                } catch (NoResultException $e) {
                    // just ignore no results \o/
                    $results = [];
                }
            } else {
                /* @var $qb \Doctrine\ORM\QueryBuilder */
                $qb = $this->getDoctrine()->getRepository('IServCoreBundle:User')->createQueryBuilder(self::class);
                
                $qb
                    ->select('p')
                    ->from('IServCoreBundle:User', 'p')
                    ->where('p.firstname LIKE :queryOriginal')
                    ->orWhere('p.lastname LIKE :queryOriginal')
                    ->orWhere('p.username LIKE :queryOriginal')
                ;
                
                $i = 0;
                foreach ($explodedQuery as $q) {
                    $qb
                        ->orWhere(sprintf('p.firstname LIKE :query%s', $i))
                        ->orWhere(sprintf('p.lastname LIKE :query%s', $i))
                        ->orWhere(sprintf('p.username LIKE :query%s', $i))
                        ->setParameter(sprintf('query%s', $i), '%'.$q.'%')
                    ;
                    
                    $i++;
                }
                
                $qb->setParameter('queryOriginal', '%'.$query.'%');
                
                try {
                    $results = $qb->getQuery()->getResult();
                } catch (NoResultException $e) {
                    // just ignore no results \o/
                    $results = [];
                }
            }
        }

        foreach ($results as $result) {
            $personal = $result->getName();
            if ($result instanceof Group) {
                $mailbox = $result->getAccount();
                $extra = 'Group';
            } else if ($result instanceof User) {
                $mailbox = $result->getUsername();
                if ($result->hasRole('ROLE_ADMIN')) {
                    $type = 'admin';
                    $extra = 'Administrator';
                } else if ($result->hasRole('ROLE_TEACHER')) {
                    $type = 'teacher';
                    $extra = 'Teacher';
                } else if($result->hasRole('ROLE_STUDENT')) {
                    $type = 'student';
                    $extra = 'Student';
                } else {
                    $extra = 'User';
                }
            }

            $host = $this->get('iserv.config')->get('Servername');
                
            $rfc822string = imap_rfc822_write_address($mailbox, $host, $personal);
                
            $suggestions[] = ['label' => $personal, 'value' => $rfc822string, 'type' => $type, 'extra' => $extra];
        }
        
        return new JsonResponse($suggestions);
    }
}
