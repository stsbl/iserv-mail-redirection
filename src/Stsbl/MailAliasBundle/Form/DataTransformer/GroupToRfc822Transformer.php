<?php
// src/Stsbl/MailAliasBundle/Form/DataTransformer/GroupToRfc822Transformer.php
namespace Stsbl\MailAliasBundle\Form\DataTransformer;

use Doctrine\ORM\NoResultException;
use Stsbl\MailAliasBundle\Entity\GroupRecipient;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
 * Transforms group to an rfc822 e-mail string and vice versa
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class GroupToRfc822Transformer implements DataTransformerInterface
{
    use ConstructorTrait;
    
    /**
     * Transforms the rfc822 string back to a object
     * 
     * @param string $addr
     * 
     * @return array<GroupRecipient>|null
     */
    public function reverseTransform($addr) 
    {
        try {
            $objects = [];
            $addr = imap_rfc822_parse_adrlist($addr, $this->config->get('Servername'));

            foreach ($addr as $a) {
                $object = new GroupRecipient();
                $repository = $this->om->getRepository('IServCoreBundle:Group');
                $group = $repository->findOneByAccount($a->mailbox);
                $object->setRecipient($group);
            
                $objects[] = $object;
            }
            
            return $objects;
        } catch (NoResultException $e) {
            // tell Smyfony that we are failed to tranform the string
            throw new TransformationFailedException('No group was found for that rfc822 string.');
        }
    }
    
    /**
     * Transforms the object to an rfc822 string
     * 
     * @param GroupRecipient|null $object
     * 
     * @return string|null
     */
    public function transform($object) 
    {
        if (isset($object)) {
            $fullName = $object->getRecipient()->getName();
            if ($object->getRecipient()->getDeleted() !== null) {
                $fullName .= sprintf(' (%s)', _('deleted'));
            }
            $localPart = $object->getRecipient()->getAccount();
            $host = $this->config->get('Servername');
        
            $object->setGroupRecipient(imap_rfc822_write_address($localPart, $host, $fullName));
            
            return $object;
        }
    }

}
