<?php
// src/Stsbl/MailRedirection/Bundle/Enity/Recipient.php
namespace Stsbl\MailRedirectionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
use Stsbl\MailRedirectionBundle\Validator\Contraints as StsblAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

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
 * StsblMailRedirectionBundle:Recipient
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenes/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_addresses")
 * @DoctrineAssert\UniqueEntity(fields="recipient", message="There is already an entry for that address.")
 */
class Address implements CrudInterface
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
     * @Assert\NotBlank()
     * @StsblAssert\SystemAddress()
     * @StsblAssert\LocalPart()
     * @ORM\Column(name="recipient", type="text")
     * 
     * @var string
     */
    private $recipient;
    
    /**
     * @ORM\Column(name="enabled", type="boolean")
     * @Assert\NotBlank()
     */
    private $enabled;

    /**
     * @ORM\Column(name="comment", type="text")
     */
    private $comment;

    /**
     * @ORM\OneToMany(targetEntity="UserRecipient", mappedBy="originalRecipient")
     *
     * @var ArrayCollection
     */
    private $userRecipients;

    /**
     * @ORM\OneToMany(targetEntity="GroupRecipient", mappedBy="originalRecipient")
     *
     * @var ArrayCollection
     */
    private $groupRecipients;
    
    /**
     * The constructor
     */
    public function __construct() 
    {
        $this->groupRecipients = new ArrayCollection();
        $this->userRecipients = new ArrayCollection();
    }
    
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
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }
    
    /**
     * Get enabled
     * 
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get comment
     * 
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
    
    /**
     * Get userRecipients
     * 
     * @return ArrayCollection
     */
    public function getUserRecipients()
    {
        return $this->userRecipients;
    }

    /**
     * Get groupRecipients
     * 
     * @return ArrayCollection
     */
    public function getGroupRecipients()
    {
        return $this->groupRecipients;
    }
    
    /**
     * Set recipient
     * 
     * @param string $recipient
     * @return Address
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        
        return $this;
    }
    
    /**
     * Set enabled
     * 
     * @param bool $enabled
     * @return Address
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        
        return $this;
    }
    
    /**
     * Set comment
     * 
     * @param string $comment
     * @return Address
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        
        return $this;
    }
    
    /**
     * Set userRecipients
     * 
     * @param ArrayCollection $userRecipients
     * @return Address
     */
    public function setUserRecipients(ArrayCollection $userRecipients)
    {
        $this->userRecipients = $userRecipients;
    }

    /**
     * Set groupRecipients
     * 
     * @param ArrayCollection $userRecipients
     * @return Address
     */
    public function setGroupRecipients(ArrayCollection $groupRecipients)
    {
        $this->userRecipients = $groupRecipients;
    }
}
