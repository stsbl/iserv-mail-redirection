<?php
// src/Stsbl/MailAliasBundle/Validator/Constraints/AddressValidiator.php
namespace Stsbl\MailAliasBundle\Validator\Constraints;

use IServ\CoreBundle\Service\Config;
use Stsbl\MailAliasBundle\Entity\Address as AddressEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
 * Validator for Address
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class AddressValidator extends ConstraintValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor to inject required classes
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Address) {
            throw new UnexpectedTypeException($constraint, Address::class);
        }

        if (!$value instanceof AddressEntity) {
            throw new UnexpectedTypeException($value, AddressEntity::class);
        }
        
        $groupRecipients = $value->getGroups()->toArray();
        $duplicatedGroupRecipients = array_unique(array_diff_assoc($groupRecipients, array_unique($groupRecipients)));
        
        foreach ($duplicatedGroupRecipients as $duplicate) {
            $this->context->buildViolation(sprintf($constraint->getDuplicateGroupMessage(), $duplicate, $value->getRecipient()))->atPath('recipient')->addViolation();
        }
        
        $userRecipients = $value->getUsers()->toArray();
        $duplicatedUserRecipients = array_unique(array_diff_assoc($userRecipients, array_unique($userRecipients)));
        
        foreach ($duplicatedUserRecipients as $duplicate) {
            $this->context->buildViolation(sprintf($constraint->getDuplicateUserMessage(), $duplicate, $value->getRecipient()))->atPath('recipient')->addViolation();
        }
    }

}
