<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Security\Privilege;

#[CoversClass(Privilege::class)]
final class PrivilegeTest extends TestCase
{
    public function testBuildsPrivilegeVoterAttributeFromUuid(): void
    {
        self::assertSame('PRIV_' . Privilege::ADMIN_UUID, Privilege::ADMIN);
    }
}
