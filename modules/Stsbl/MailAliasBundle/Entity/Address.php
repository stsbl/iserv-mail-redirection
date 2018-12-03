<?php declare(strict_types = 1);

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
     *
     * @var bool
     */
    private $enabled;

    /**
     * @ORM\Column(name="comment", type="text")
     *
     * @var string
     */
    private $comment;

    /**
     * @ORM\ManyToMany(targetEntity="IServ\CoreBundle\Entity\User")
     * @ORM\JoinTable(name="mailredirection_recipient_users",
     *      joinColumns={@ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="recipient", referencedColumnName="act", unique=true)}
     * )
     *
     * @var User[]|ArrayCollection
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="IServ\CoreBundle\Entity\Group")
     * @ORM\JoinTable(name="mailredirection_recipient_groups",
     *      joinColumns={@ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="recipient", referencedColumnName="act", unique=true)}
     * )
     *
     * @var Group[]|ArrayCollection
     */
    private $groups;

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
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
    
    /**
     * @return User[]|ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return Group[]|ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * @return $this
     */
    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;
        
        return $this;
    }

    /**
     * @return $this
     */
    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;
        
        return $this;
    }

    /**
     * @return $this
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        
        return $this;
    }

    /**
     * @return $this
     */
    public function addUser(User $user): self
    {
        $this->users->add($user);

        return $this;
    }

    /**
     * removes one user recipient from this original recipient
     *
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function hasUser(User $user): bool
    {
        return $this->users->contains($user);
    }

    /**
     * @return $this
     */
    public function addGroup(Group $group): self
    {
        $this->groups->add($group);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeGroup(Group $group): self
    {
        $this->groups->removeElement($group);

        return $this;
    }

    public function hasGroup(Group $group): bool
    {
        return $this->groups->contains($group);
    }
}
