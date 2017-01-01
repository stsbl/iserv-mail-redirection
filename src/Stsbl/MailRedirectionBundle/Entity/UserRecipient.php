<?php
// src/Stsbl/MailRedirectionBundle/Entity/UserRecipient.php
namespace Stsbl\MailRedirectionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Entity\CrudInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2016 Felix Jacobi.
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
 * StsblMailRedirectionBundle:UserRecipient
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_recipient_users")
 * @DoctrineAssert\UniqueEntity(fields="recipient", message="This user has already an entry for the original recipient.")
 * @DoctrineAssert\UniqueEntity(fields="originalRecipient", message="This recipient has already an entry for the user.")
 */
class UserRecipient implements CrudInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Address", inversedBy="userRecipients")
     * @ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank()
     * 
     * @var Address
     */
    private $originalRecipient;

    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="recipient", referencedColumnName="act")
     * @Assert\NotBlank()
     * 
     * @var User
     */
    private $recipient;
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->recipient;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get recipient
     * 
     * @return User
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Get original recipient
     * 
     * @return Address
     */
    public function getOriginalRecipient()
    {
        return $this->originalRecipient;
    }     

    /**
     * Get recipient
     * 
     * @param User $recipient
     * @return UserRecipient
     */
    public function setRecipient(User $recipient)
    {
        $this->recipient = $recipient;
        
        return $this;
    }

    /**
     * Set original recipient
     * 
     * @param Addressnew $originalRecipient
     * @return UserRecipient
     */
    public function setOriginalRecipient(Address $originalRecipient)
    {
        $this->originalRecipient = $originalRecipient;
        
        return $this;
    }

}
