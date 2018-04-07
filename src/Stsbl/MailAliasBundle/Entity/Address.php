<?php
// src/Stsbl/MailAlias/Bundle/Enity/Recipient.php
namespace Stsbl\MailAliasBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Entity\CrudInterface;
use Stsbl\MailAliasBundle\Validator\Constraints as StsblAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
 * StsblMailAliasBundle:Address
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenes/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_addresses")
 * @DoctrineAssert\UniqueEntity(fields="recipient", message="There is already an entry for that address.")
 * @StsblAssert\Address()
 */
class Address implements CrudInterface
{
    const CRUD_ICON = 'message-forward';

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
     * @StsblAssert\NotAccount()
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
     * @ORM\ManyToMany(targetEntity="IServ\CoreBundle\Entity\User")
     * @ORM\JoinTable(name="mailredirection_recipient_users",
     *      joinColumns={@ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="recipient", referencedColumnName="act", unique=true)}
     * )
     *
     * @var User[]
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="IServ\CoreBundle\Entity\Group")
     * @ORM\JoinTable(name="mailredirection_recipient_groups",
     *      joinColumns={@ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="recipient", referencedColumnName="act", unique=true)}
     * )
     *
     * @var Group[]
     */
    private $groups;
    
    /**
     * The constructor
     */
    public function __construct() 
    {
        $this->groups = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Get groupRecipients
     * 
     * @return Group[]
     */
    public function getGroups()
    {
        return $this->groups;
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
     * Adds one user to this original recipient
     *
     * @param User $user
     * @return Address
     */
    public function addUser(User $user)
    {
        $this->users->add($user);

        return $this;
    }

    /**
     * removes one user recipient from this original recipient
     *
     * @param User $user
     * @return Address
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasUser(User $user)
    {
        return $this->users->contains($user);
    }

    /**
     * Adds one user to this original recipient
     *
     * @param Group $group
     * @return Address
     */
    public function addGroup(Group $group)
    {
        $this->groups->add($group);

        return $this;
    }

    /**
     * removes one group recipient from this original recipient
     *
     * @param Group $group
     * @return Address
     */
    public function removeGroup(Group $group)
    {
        $this->groups->removeElement($group);

        return $this;
    }

    /**
     * @param Group $group
     * @return bool
     */
    public function hasGroup(Group $group)
    {
        return $this->groups->contains($group);
    }
}
