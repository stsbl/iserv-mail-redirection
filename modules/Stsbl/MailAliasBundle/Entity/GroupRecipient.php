<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_recipient_groups")
 */
final class GroupRecipient
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private ?int $id = null;

    /** @ORM\Column(name="recipient", type="text") */
    private string $account;

    /** @ORM\ManyToOne(targetEntity="Stsbl\\MailAliasBundle\\Entity\\Address", inversedBy="groups") @ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id", onDelete="CASCADE") */
    private Address $address;

    public function __construct(string $account)
    {
        $this->account = $account;
    }

    public function getAccount(): string
    {
        return $this->account;
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
