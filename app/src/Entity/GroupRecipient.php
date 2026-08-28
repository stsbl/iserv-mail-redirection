<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\Library\Uuid\UuidInterface;
use Stsbl\IServ\MailRedirection\Doctrine\GroupAccountType;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Repository\GroupRecipientRepository;

#[ORM\Entity(repositoryClass: GroupRecipientRepository::class)]
#[ORM\Table(name: 'mailredirection_recipient_groups')]
class GroupRecipient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'recipient', type: GroupAccountType::NAME)]
    private GroupAccount $account;

    #[ORM\Column(name: 'uuid', type: 'iserv_uuid', nullable: true)]
    private ?UuidInterface $uuid = null;

    #[ORM\ManyToOne(targetEntity: Address::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'original_recipient_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Address $address;

    public function __construct(GroupAccount $account, ?UuidInterface $uuid = null)
    {
        $this->account = $account;
        $this->uuid = $uuid;
    }

    public function getAccount(): GroupAccount
    {
        return $this->account;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setAccount(GroupAccount $account): void
    {
        $this->account = $account;
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
        return (string) $this->account;
    }
}
