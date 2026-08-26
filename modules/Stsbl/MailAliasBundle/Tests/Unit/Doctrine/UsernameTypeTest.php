<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use IServ\Library\User\User\Username;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Doctrine\UsernameType;

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
