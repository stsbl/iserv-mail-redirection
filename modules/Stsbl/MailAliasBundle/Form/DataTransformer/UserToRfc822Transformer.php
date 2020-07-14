<?php declare(strict_types = 1);

namespace Stsbl\MailAliasBundle\Form\DataTransformer;

use IServ\CoreBundle\Entity\User;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

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
 * Transforms user to an rfc822 e-mail string and vice versa
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class UserToRfc822Transformer implements DataTransformerInterface
{
    use ConstructorTrait;
    
    /**
     * {@inheritdoc}
     *
     * Transforms the rfc822 string back to an object
     *
     * @param array $value
     */
    public function reverseTransform($value): ?User
    {
        $domain = $this->config->get('Domain');
        $value = imap_rfc822_parse_adrlist($value['userRecipient'], $domain);

        foreach ($value as $address) {
            $repository = $this->em->getRepository(User::class);
            $user = $repository->findOneBy(['username' => $address->mailbox]);

            if (null === $user) {
                throw new TransformationFailedException('No user was found for that rfc822 string.');
            }

            if ($address->host !== $domain) {
                throw new TransformationFailedException('Invalid domain in rfc822 string.');
            }

            return $user;
        }

        return null;
    }
    
    /**
     * {@inheritdoc}
     *
     * Transforms the object to an rfc822 string
     *
     * @param User|null $object
     */
    public function transform($object): ?array
    {
        if (null !== $object) {
            if (!$object instanceof User) {
                throw new UnexpectedTypeException($object, User::class);
            }

            $fullName = $object->getName();
            $localPart = $object->getUsername();
            $host = $this->config->get('Domain');

            return ['userRecipient' => imap_rfc822_write_address($localPart, $host, $fullName)];
        }

        return null;
    }
}
