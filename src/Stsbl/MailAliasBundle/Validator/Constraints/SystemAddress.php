<?php
// src/Stsbl/MailAliasBundle/Validator/Constraints/SystemAddress.php
namespace Stsbl\MailAliasBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
 * Validates that given address is not a system mail address
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @Annotation
 */
class SystemAddress extends Constraint 
{
    /**
     * get message
     * 
     * @return string
     */
    public function getMessage()
    {
        return _('This is a system e-mail address, you are not allowed to create redirections for it.');
    }
    
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
    
    /**
     * {@inheritdoc]
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
