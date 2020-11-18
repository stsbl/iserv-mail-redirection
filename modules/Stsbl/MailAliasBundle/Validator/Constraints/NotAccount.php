<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licneses/MIT>
 * @Annotation
 */
class NotAccount extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return NotAccountValidator::class;
    }

    /**
     * {@inheritdoc]
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /* Message functions */

    /**
     * @return string
     */
    public function getUserMessage()
    {
        return _('A user with the e-mail address %s does already exists.');
    }

    /**
     * @return string
     */
    public function getGroupMessage()
    {
        return _('A group with the e-mail address %s does already exists.');
    }

    /**
     * @return string
     */
    public function getSystemAccountMessage()
    {
        return _('A system account %s does already exists.');
    }
}
