<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Domain\Username;
use Stsbl\IServ\MailRedirection\Doctrine\UsernameType;

#[CoversClass(UsernameType::class)]
final class UsernameTypeTest extends TestCase
{
    public function testConvertsUsernamesBetweenTheDomainAndDatabase(): void
    {
        $type = new UsernameType();
        $platform = new PostgreSQLPlatform();

        self::assertSame(UsernameType::NAME, $type->getName());
        self::assertEquals(new Username('max'), $type->convertToPHPValue('max', $platform));
        self::assertSame('max', $type->convertToDatabaseValue(new Username('max'), $platform));
    }
}
