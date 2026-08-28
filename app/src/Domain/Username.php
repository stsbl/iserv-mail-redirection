<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Domain;

/**
 * A local recipient account name.
 *
 * The database representation is intentionally a domain value instead of an
 * IDM entity because recipient join tables must stay available to Exim.
 */
final class Username
{
    public function __construct(private readonly string $username)
    {
        if ($username === '') {
            throw new \InvalidArgumentException('Username cannot be empty.');
        }
    }

    public static function import(string $username): self
    {
        return new self($username);
    }

    public function export(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function __toString(): string
    {
        return $this->username;
    }
}
