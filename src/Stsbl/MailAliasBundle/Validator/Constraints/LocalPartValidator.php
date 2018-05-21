<?php
// src/Stsbl/MailAliasBundle/Validator/Constraints/LocalPartValidator.php
namespace Stsbl\MailAliasBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/*
 * The MIT License
 *
 * Copyright 2018 Felix jacobi.
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
 * Validate that given address is a valid local part.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class LocalPartValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof LocalPart) {
            throw new UnexpectedTypeException($constraint, LocalPart::class);
        }

        // use random domain, it does matter, which one is behind the local part.
        if (!filter_var($value . '@example.com', \FILTER_VALIDATE_EMAIL)) {
            if (false !== strpos($value, '@')) {
                $this->context->buildViolation($constraint->getMessageForAt())
                    ->addViolation();
            } elseif (preg_match('/(.*)[äöüß](.*)/i', $value)) {
                $this->context->buildViolation($constraint->getMessageForUmlauts())
                    ->addViolation();                
            } 
            // show generic message if there is no custom message
            else {
                $this->context->buildViolation($constraint->getMessage())
                    ->addViolation();
            }
        }
    }

}
