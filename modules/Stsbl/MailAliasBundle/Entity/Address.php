<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\Library\User\User\Username;
use Stsbl\MailAliasBundle\Domain\GroupAccount;
use Stsbl\MailAliasBundle\Repository\AddressRepository;
use Stsbl\MailAliasBundle\Validator\Constraints as StsblAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'mailredirection_addresses')]
#[DoctrineAssert\UniqueEntity(fields: 'recipient', message: 'There is already an entry for that address.')]
#[StsblAssert\Address]
class Address implements CrudInterface
{
    public const CRUD_ICON = 'message-forward';

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[Assert\NotBlank]
    #[StsblAssert\SystemAddress]
    #[StsblAssert\LocalPart]
    #[StsblAssert\NotAccount]
    #[ORM\Column(name: 'recipient', type: 'text')]
    private ?string $recipient;

    #[ORM\Column(name: 'enabled', type: 'boolean')]
    #[Assert\NotBlank]
    private bool $enabled = true;

    #[ORM\Column(name: 'display_name', type: 'text', nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(name: 'comment', type: 'text')]
    private ?string $comment;

    /** @var UserRecipient[]&Collection */
    #[ORM\OneToMany(targetEntity: UserRecipient::class, mappedBy: 'address', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $users;

    /** @var GroupRecipient[]&Collection */
    #[ORM\OneToMany(targetEntity: GroupRecipient::class, mappedBy: 'address', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->recipient ?? '?';
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

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * @return UserRecipient[]&Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return GroupRecipient[]&Collection
     */
    public function getGroups(): Collection
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

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return $this
     */
    public function addUser(UserRecipient $user): self
    {
        $user->setAddress($this);
        $this->users->add($user);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeUser(UserRecipient $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function hasUser(Username $username): bool
    {
        return $this->users->exists(static fn (int $key, UserRecipient $recipient): bool => $recipient->getUsername()->getUsername() === $username->getUsername());
    }

    /**
     * @return $this
     */
    public function addGroup(GroupRecipient $group): self
    {
        $group->setAddress($this);
        $this->groups->add($group);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeGroup(GroupRecipient $group): self
    {
        $this->groups->removeElement($group);

        return $this;
    }

    public function hasGroupAccount(GroupAccount $account): bool
    {
        return $this->groups->exists(static fn (int $key, GroupRecipient $recipient): bool => $recipient->getAccount()->export() === $account->export());
    }

    /** @return list<AutocompleteTagsData> */
    public function getRecipients(): array
    {
        $recipients = [];
        foreach ($this->users as $recipient) {
            $recipients[] = new AutocompleteTagsData('user:' . $recipient->getUsername(), (string) $recipient->getUsername(), 'user');
        }
        foreach ($this->groups as $recipient) {
            $recipients[] = new AutocompleteTagsData('group:' . $recipient->getAccount(), (string) $recipient->getAccount(), 'group');
        }

        return $recipients;
    }

    /** @param iterable<AutocompleteTagsData> $recipients */
    public function setRecipients(iterable $recipients): self
    {
        $users = [];
        $groups = [];
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof AutocompleteTagsData || $recipient->getId() === null) {
                continue;
            }
            if ($recipient->getSource() === 'user') {
                $users[] = new Username($recipient->getId());
            }
            if ($recipient->getSource() === 'group') {
                $groups[] = new GroupAccount($recipient->getId());
            }
        }

        foreach ($this->users->toArray() as $recipient) {
            if (!in_array($recipient->getUsername(), $users, false)) {
                $this->removeUser($recipient);
            }
        }
        foreach ($this->groups->toArray() as $recipient) {
            if (!in_array($recipient->getAccount(), $groups, false)) {
                $this->removeGroup($recipient);
            }
        }
        foreach ($users as $username) {
            if (!$this->hasUser($username)) {
                $this->addUser(new UserRecipient($username));
            }
        }
        foreach ($groups as $account) {
            if (!$this->hasGroupAccount($account)) {
                $this->addGroup(new GroupRecipient($account));
            }
        }

        return $this;
    }
}
