<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\Library\User\User\Username;
use IServ\Library\Uuid\UuidInterface;
use Stsbl\MailAliasBundle\Doctrine\UsernameType;
use Stsbl\MailAliasBundle\Repository\UserRecipientRepository;

#[ORM\Entity(repositoryClass: UserRecipientRepository::class)]
#[ORM\Table(name: 'mailredirection_recipient_users')]
final class UserRecipient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'recipient', type: UsernameType::NAME)]
    private Username $username;

    #[ORM\Column(name: 'uuid', type: 'iserv_uuid', nullable: true)]
    private ?UuidInterface $uuid = null;

    #[ORM\ManyToOne(targetEntity: Address::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'original_recipient_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Address $address;

    public function __construct(Username $username, ?UuidInterface $uuid = null)
    {
        $this->username = $username;
        $this->uuid = $uuid;
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUsername(Username $username): void
    {
        $this->username = $username;
    }

    public function setUuid(UuidInterface $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function __toString(): string
    {
        return (string) $this->username;
    }
}
