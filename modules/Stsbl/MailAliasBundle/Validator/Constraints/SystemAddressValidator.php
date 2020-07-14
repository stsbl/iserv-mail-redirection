<?php
// src/Stsbl/MailAliasBundle/Validator/Contraints/SystemAddressValidator.php
namespace Stsbl\MailAliasBundle\Validator\Constraints;

use IServ\CoreBundle\Service\Config;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/*
 * The MIT License
 *
 * Copyright 2020 Felix Jacobi.
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
 * Validate that given address is not a system mail address.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class SystemAddressValidator extends ConstraintValidator
{
    private const REGEX_SYSTEM = '/^(root|postmaster|mailer-daemon|nobody|hostmaster|usenet|news|webmaster|ftp|abuse|'.
        'noc|security|monit|clamav|www-data)$/';

    /**
     * @var Config
     */
    private $config;

    /**
     * The constructor
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
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SystemAddress) {
            throw new UnexpectedTypeException($constraint, SystemAddress::class);
        }

        // disallow redirection of system mails, this must be done via /etc/aliases.
        if (preg_match(self::REGEX_SYSTEM, $value)) {
            $this->context
                ->buildViolation(sprintf($constraint->getMessage(), $value . '@' . $this->config->get('Domain')))
                ->addViolation()
            ;
        }
    }
}
