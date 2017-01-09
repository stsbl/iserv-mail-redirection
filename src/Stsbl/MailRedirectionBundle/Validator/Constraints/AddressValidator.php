<?php
// src/Stsbl/MailRedirectionBundle/Validator/Constraints/GroupRecipientValidator.php
namespace Stsbl\MailRedirectionBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Service\Config;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
 * Validator for GroupRecipient
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class AddressValidator extends ConstraintValidator
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EntityManager
     */
    protected $em;
    
    /**
     * Constructor to inject required classes
     * 
     * @param Config $config
     * @param ObjectManager $om
     */
    public function __construct(Config $config = null, EntityManager $em = null)
    {
        if (!isset($config)) {
            throw new \RuntimeException('config is empty, did you forget to pass it to the constructor?');
        }
        
        if (!isset($em)) {
            throw new \RuntimeException('em is empty, did you forget to pass it to the constructor?');
        }
        
        $this->config = $config;
        $this->em = $em;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($address, Constraint $constraint) 
    {
        /* @var $address \Stsbl\MailRedirectionBundle\Entity\Address */
        /* @var $constraint Address */
        
        $groupRecipients = $address->getGroupRecipients()->toArray();
        $duplicatedGroupRecipients = array_unique(array_diff_assoc($groupRecipients, array_unique($groupRecipients)));
        
        foreach ($duplicatedGroupRecipients as $duplicate) {
            $this->context->buildViolation(sprintf($constraint->getDuplicateGroupMessage(), $duplicate))->atPath('recipient')->addViolation();
        }
        
        $userRecipients = $address->getUserRecipients()->toArray();
        $duplicatedUserRecipients = array_unique(array_diff_assoc($userRecipients, array_unique($userRecipients)));
        
        foreach ($duplicatedUserRecipients as $duplicate) {
            $this->context->buildViolation(sprintf($constraint->getDuplicateUserMessage(), $duplicate))->atPath('recipient')->addViolation();
        }
    }

}
