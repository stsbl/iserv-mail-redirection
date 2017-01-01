<?php
// src/Stsbl/MailRedirectionBundle/Entity/UserRecipient.php
namespace Stsbl\MailRedirectionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\Group;
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
 * StsblMailRedirectionBundle:GroupRecipient
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_recipient_groups")
 * * @DoctrineAssert\UniqueEntity(fields={"recipient", "originalRecipient"}, message="This combination of group and original recipient already has an entry.")
 * //@DoctrineAssert\UniqueEntity(fields="recipient", message="This group has already an entry for the original recipient.")
 * //@DoctrineAssert\UniqueEntity(fields="originalRecipient", message="This recipient has already an entry for the group.")
 */
class GroupRecipient implements CrudInterface
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
     * @ORM\ManyToOne(targetEntity="Address", inversedBy="groupRecipients")
     * @ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * 
     * @var Address
     */
    private $originalRecipient;

    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="recipient", referencedColumnName="act")
     * @Assert\NotBlank()
     * 
     * @var Group
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
     * @return Group
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
     * @param Group $recipient
     * @return GroupRecipient
     */
    public function setRecipient(Group $recipient)
    {
        $this->recipient = $recipient;
        
        return $this;
    }

    /**
     * Set original recipient
     * 
     * @param Address $originalRecipient
     * @return GroupRecipient
     */
    public function setOriginalRecipient(Address $originalRecipient)
    {
        $this->originalRecipient = $originalRecipient;
        
        return $this;
    }

}
