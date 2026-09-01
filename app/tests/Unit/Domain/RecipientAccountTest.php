<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;

#[CoversClass(Username::class)]
#[CoversClass(GroupAccount::class)]
final class RecipientAccountTest extends TestCase
{
    public function testExportsLocalRecipientAccounts(): void
    {
        $username = Username::import('alice');
        $group = GroupAccount::import('teachers');

        self::assertSame('alice', $username->getUsername());
        self::assertSame('alice', $username->export());
        self::assertSame('alice', (string) $username);
        self::assertSame('teachers', $group->export());
        self::assertSame('teachers', (string) $group);
    }

    public function testRejectsEmptyRecipientAccounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Username('');
    }

    public function testRejectsEmptyGroupAccount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new GroupAccount('');
    }
}
