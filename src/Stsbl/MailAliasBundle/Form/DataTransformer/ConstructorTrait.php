<?php
// src/Stsbl/MailAliasBundle/Form/DataTransformer/ContructorTrait.php
namespace Stsbl\MailAliasBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use IServ\CoreBundle\Service\Config;

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
 * Common trait for both transformers
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
trait ConstructorTrait 
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ObjectManager
     */
    protected $om;
    
    /**
     * Constructor to inject required classes
     * 
     * @param Config $config
     * @param ObjectManager $om
     */
    public function __construct(Config $config = null, ObjectManager $om = null)
    {
        if (!isset($config)) {
            throw new \RuntimeException('config is empty, did you forget to pass it to the constructor?');
        }
        
        if (!isset($om)) {
            throw new \RuntimeException('om is empty, did you forget to pass it to the constructor?');
        }
        
        $this->config = $config;
        $this->om = $om;
    }
}
