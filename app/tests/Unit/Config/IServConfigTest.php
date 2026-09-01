<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Config;

use IServ\Library\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Config\IServConfig;

#[CoversClass(IServConfig::class)]
final class IServConfigTest extends TestCase
{
    public function testReturnsConfiguredDomain(): void
    {
        $config = new IServConfig(new Config(['Domain' => 'example.test']));

        self::assertSame('example.test', $config->domain());
    }
}
