<?php
// src/Stsbl/MailAliasBundle/Controler/MailAliasController.php
namespace Stsbl\MailAliasBundle\Controller;

use Doctrine\ORM\NoResultException;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CrudBundle\Controller\CrudController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\MailAliasBundle\Admin\AddressAdmin;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

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
 */
class MailAliasController extends CrudController 
{
    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request) 
    {
        $ret = parent::indexAction($request);
        
        if (is_array($ret)) {
            $ret['importForm'] = $this->getImportForm()->createView();
        }
        
        return $ret;
    }

    /**
     * Get auto-completion suggestions for users and groups
     * 
     * @Method("GET")
     * @Route("admin/mailaliases/recipients", name="admin_mail_aliases_recipients", options={"expose"=true})
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
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
        
        // require minimum string length to prevent script timeouts due to too much database results :/
        if (strlen($query) < 3) {
            return new JsonResponse([[
                'label' => 'Too much results, please enter more specific term.', 
                'value' => '', 
                'type' => 'notice',
                'extra' => ''
            ]]);
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
                    ->orWhere('p.username LIKE :queryAct')
                ;
                
                $i = 0;
                $length = count($explodedQuery);
                $halfLengthUp = round($length / 2, 0, PHP_ROUND_HALF_UP);
                $halfLengthDown = round($length / 2, 0, PHP_ROUND_HALF_DOWN);
                
                foreach ($explodedQuery as $q) {
                    // ignore empty strings, this would to that the search condition is %%, which means
                    // ALL entries in database and that usually lead to a scricpt execution timeout and 
                    // makes the JSON Backend slow!
                    if (!empty($q)) {
                        // assume that the first half of the query is the first name, the second one
                        // account name, thjis should work for almost all cases.
                        if ($i < $halfLengthUp) {
                            $qb->orWhere(sprintf('p.firstname LIKE :query%s', $i));
                        } else {
                            $qb->orWhere(sprintf('p.lastname LIKE :query%s', $i));
                        }
                        
                        if ($i < $halfLengthDown) {
                            $qb->orWhere(sprintf('p.firstname LIKE :query%s', $i));
                        } else {
                            $qb->orWhere(sprintf('p.lastname LIKE :query%s', $i));
                        }
                        
                        $qb->setParameter(sprintf('query%s', $i), '%'.$q.'%');
                    }
                    
                    $i++;
                }
                
                $qb->setParameter('queryOriginal', '%'.$query.'%');
                
                // transform full name into an account
                $act = strtolower(str_replace(' ', '.', $query));
                $qb->setParameter('queryAct', '%'.$act.'%');
                
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
            $label = $personal;
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
                
                if(!empty($result->getAuxInfo())) {
                    $label .= sprintf(' (%s)', $result->getAuxInfo());
                }
            }

            $host = $this->get('iserv.config')->get('Servername');
            $rfc822string = imap_rfc822_write_address($mailbox, $host, $personal);
            $suggestions[] = ['label' => $label, 'value' => $rfc822string, 'type' => $type, 'extra' => $extra];
        }
        
        return new JsonResponse($suggestions);
    }
    
    /**
     * Imports a submitted csv file
     * 
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("admin/mailaliases/import", name="admin_mail_aliases_import")
     * @Security("is_granted('PRIV_MAIL_REDIRECTION_ADMIN')")
     * @Template()
     */
    public function importAction(Request $request)
    {
        $form = $this->getImportForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            /* @var $importer \Stsbl\MailAliasBundle\Service\Importer */
            $importer = $this->get('stsbl.mailalias.service.importer');
            
            try {
                $importer->setEnableNewAliases((bool)$data['enable']);
                $importer->setUploadedFile($data['file']);
                $importer->transform();
                
                $warnings = [];
                foreach ($importer->getWarnings() as $w) {
                    $warnings[] = $w;
                }
                
                if (count($warnings) > 0) {
                    $this->get('iserv.flash')->alert(implode("\n", $warnings));
                }
                
                /* @var $logger \IServ\CoreBundle\Service\Logger */
                $logger = $this->get('iserv.logger');
                $servername = $this->get('iserv.config')->get('Servername');
                $module = 'Mail aliases';
                $messages = [];
                
                /* @var $newAddresses \Stsbl\MailAliasBundle\Entity\Address[] */
                $newAddresses = $importer->getNewAddresses();
                foreach ($newAddresses as $a) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_ALIAS_ADDED, (string)$a, $servername), $module);
                    $messages[] = __('Added alias %s@%s.', (string)$a, $servername);
                }
                
                /* @var $newUserRecipients \Stsbl\MailAliasBundle\Entity\UserRecipient[] */
                $newUserRecipients = $importer->getNewUserRecipients();
                foreach ($newUserRecipients as $u) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_USER_RECIPEINT_ADDED, (string)$u, (string)$u->getOriginalRecipient(), $servername), $module);
                    $messages[] = __('Added user %s as recipient for alias %s@%s.', (string)$u, (string)$u->getOriginalRecipient(), $servername);
                }
                
                /* @var $newGroupRecipients \Stsbl\MailAliasBundle\Entity\GroupRecipient[] */
                $newGroupRecipients = $importer->getNewGroupRecipients();
                foreach ($newGroupRecipients as $g) {
                    $logger->writeForModule(sprintf(AddressAdmin::LOG_GROUP_RECIPIENT_ADDED, (string)$g, (string)$g->getOriginalRecipient(), $servername), $module);
                    $messages[] = __('Added group %s as recipient for alias %s@%s.', (string)$g, (string)$g->getOriginalRecipient(), $servername);
                }
                
                if (count($messages) > 0) 
                    $this->get('iserv.flash')->success(implode("\n", $messages));
                
                return new RedirectResponse($this->generateUrl('admin_mail_aliases_index'));
            } catch (ImportException $e) {
                $message = $e->getMessage();
                $line = $e->getFileLine();
                
                if ($message === ImportException::MESSAGE_INVALID_COLUMN_AMOUNT) {
                    $message = str_replace('.', '', $message);
                    if (!is_null($line)) {
                        $message .= ' near line %s.';
                    } else {
                        $message .= '.';
                    }
                    
                    $message = __($message, $line);
                } else {
                    $message = _($message);
                }
                
                $this->get('iserv.flash')->error($message);
            }
        }
        
        // track path
        $this->addBreadcrumb(_('Mail aliases'), $this->generateUrl('admin_mail_aliases_index'));
        $this->addBreadcrumb(_('Import'));
        
        return [
            'importForm' => $form->createView(),
            'importExplanation' => AddressAdmin::getImportExplanation(),
            'importExplanationFieldList' => AddressAdmin::getImportExplanationFieldList()
        ];
    }
    
    /**
     * Gets an form for csv import
     * 
     * @return \Symfony\Component\Form\Form
     */
    private function getImportForm()
    {
        /* @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $this->get('form.factory')->createNamedBuilder('mail_alias_import');
        
        $builder
            ->setAction($this->generateUrl('admin_mail_aliases_import'))
            ->add('file', FileType::class, [
                'label' => false,
                'constraints' => [new NotBlank(['message' => _('Please select a CSV file for import.')])]
            ])
            ->add('enable', BooleanType::class, [
                'label' => false,
                'choices' => [
                    _('Enable new aliases') => '1',
                    _('Disable new aliases') => '0',
                ],
                'constraints' => [new NotBlank()]
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Import'),
                'buttonClass' => 'btn-success',
                'icon' => 'pro-file-import'
            ])
        ;
        
        return $builder->getForm();
    }
}
