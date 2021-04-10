<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
 * Validates that given address is a valid local part.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/liceneses/MIT>
 * @Annotation
 */
class LocalPart extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return LocalPartValidator::class;
    }
    
    /**
     * {@inheritdoc]
     */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /* Message functions */

    /**
     * Get message for invalid address.
     */
    public function getMessage(): string
    {
        return _('This is not a valid local part of an e-mail address.');
    }

    /**
     * Get message for invalid address if an @ is contained in it.
     */
    public function getMessageForAt(): string
    {
        return _('Enter only the address part before the @.');
    }

    /**
     * Get message for invalid address if an ä,ö,ü,ß is contained in it.
     */
    public function getMessageForUmlauts(): string
    {
        return _('Umlauts are not allowed as part of it.');
    }
}
