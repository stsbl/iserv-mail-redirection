<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\Library\User\User\Username;
use Stsbl\MailAliasBundle\Doctrine\UsernameType;

/**
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_recipient_users")
 */
final class UserRecipient
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private ?int $id = null;

    /** @ORM\Column(name="recipient", type=UsernameType::NAME) */
    private Username $username;

    /** @ORM\ManyToOne(targetEntity="Stsbl\\MailAliasBundle\\Entity\\Address", inversedBy="users") @ORM\JoinColumn(name="original_recipient_id", referencedColumnName="id", onDelete="CASCADE") */
    private Address $address;

    public function __construct(Username $username)
    {
        $this->username = $username;
    }

    public function getUsername(): Username
    {
        return $this->username;
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
