<?php
// src/Stsbl/MailAliasBundle/Validator/Constraints/NotAccountValidator.php
namespace Stsbl\MailAliasBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use IServ\ApiBundle\Entity\User;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Service\Config;
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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licneses/MIT>
 */
class NotAccountValidator extends ConstraintValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(Config $config, EntityManagerInterface $em)
    {
        $this->config = $config;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        // ignore null values
        if (null === $value) {
            return;
        }

        if (!$constraint instanceof NotAccount) {
            throw new UnexpectedTypeException($constraint, NotAccount::class);
        }

        if ($this->em->find(User::class, $value) !== null) {
            $this->context->addViolation(sprintf(
                $constraint->getUserMessage(),
                $value . '@' . $this->config->get('Domain')
            ));
            return;
        }

        if ($this->em->find(Group::class, $value) !== null) {
            $this->context->addViolation(sprintf(
                $constraint->getGroupMessage(),
                $value . '@' . $this->config->get('Domain')
            ));

            return;
        }

        if (posix_getpwnam($value)) {
            $this->context->addViolation(sprintf($constraint->getSystemAccountMessage(), $value));
        }
    }
}
