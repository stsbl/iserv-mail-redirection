<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\Library\Uuid\UuidInterface;
use Stsbl\MailAliasBundle\Repository\GroupRecipientRepository;

/**
 * @ORM\Entity(repositoryClass="Stsbl\\MailAliasBundle\\Repository\\GroupRecipientRepository")
 * @ORM\Table(name="mailredirection_recipient_groups")
 */
final class GroupRecipient
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private ?int $id = null;

    /** @ORM\Column(name="recipient", type="text") */
    private string $account;

    /** @ORM\Column(name="uuid", type="iserv_uuid", nullable=true) */
    private ?UuidInterface $uuid = null;

    /** @ORM\ManyToOne(targetEntity="Stsbl\\MailAliasBundle\\Entity\\Address", inversedBy="groups") @ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id", onDelete="CASCADE") */
    private Address $address;

    public function __construct(string $account, ?UuidInterface $uuid = null)
    {
        $this->account = $account;
        $this->uuid = $uuid;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setAccount(string $account): void
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
        return $this->account;
    }
}
