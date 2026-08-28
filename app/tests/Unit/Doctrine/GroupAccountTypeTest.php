<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Doctrine\GroupAccountType;

#[CoversClass(GroupAccountType::class)]
final class GroupAccountTypeTest extends TestCase
{
    public function testConvertsGroupAccountsBetweenTheDomainAndDatabase(): void
    {
        $type = new GroupAccountType();
        $platform = new PostgreSQLPlatform();

        self::assertSame(GroupAccountType::NAME, $type->getName());
        self::assertEquals(new GroupAccount('max'), $type->convertToPHPValue('max', $platform));
        self::assertSame('max', $type->convertToDatabaseValue(new GroupAccount('max'), $platform));
    }
}
