<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Domain;

final class GroupAccount
{
    public function __construct(private readonly string $account)
    {
        if ($account === '') {
            throw new \InvalidArgumentException('Group account cannot be empty.');
        }
    }

    public static function import(string $account): self
    {
        return new self($account);
    }

    public function export(): string
    {
        return $this->account;
    }

    public function __toString(): string
    {
        return $this->account;
    }
}
