<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Entity;

use IServ\Library\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;

#[CoversClass(UserRecipient::class)]
#[CoversClass(GroupRecipient::class)]
final class RecipientEntityTest extends TestCase
{
    public function testKeepsUserRecipientAccountAndUuid(): void
    {
        $uuid = Uuid::createFromNormalized('0fd3d1ec-5146-47ef-8c11-70f23850dd8e');
        $recipient = new UserRecipient(new Username('alice'));
        $recipient->setUuid($uuid);
        $recipient->setUsername(new Username('bob'));
        $recipient->setAddress(new Address());

        self::assertSame('bob', (string) $recipient);
        self::assertSame('bob', $recipient->getUsername()->export());
        self::assertSame($uuid, $recipient->getUuid());
    }

    public function testKeepsGroupRecipientAccountAndUuid(): void
    {
        $uuid = Uuid::createFromNormalized('10d3d1ec-5146-47ef-8c11-70f23850dd8e');
        $recipient = new GroupRecipient(new GroupAccount('teachers'));
        $recipient->setUuid($uuid);
        $recipient->setAccount(new GroupAccount('staff'));
        $recipient->setAddress(new Address());

        self::assertSame('staff', (string) $recipient);
        self::assertSame('staff', $recipient->getAccount()->export());
        self::assertSame($uuid, $recipient->getUuid());
    }
}
