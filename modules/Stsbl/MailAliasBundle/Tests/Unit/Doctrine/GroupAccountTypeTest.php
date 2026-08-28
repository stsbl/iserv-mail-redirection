<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Stsbl\MailAliasBundle\Domain\GroupAccount;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Doctrine\GroupAccountType;

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
