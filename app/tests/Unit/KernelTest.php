<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    public function testConfiguresThePortalWebMockSessionInTests(): void
    {
        $kernel = new Kernel('test', true);
        $container = new ContainerBuilder();
        $container->register('session.storage.factory.mock_file')->setArgument('$name', 'old');

        $kernel->process($container);

        self::assertSame('IServPortalWebSession', $container->getDefinition('session.storage.factory.mock_file')->getArgument('$name'));
        $getModule = new \ReflectionMethod($kernel, 'getModule');
        self::assertSame('stsbl-iserv-mail-redirection', $getModule->invoke($kernel));
    }

    public function testDoesNotChangeProductionSessionConfiguration(): void
    {
        $kernel = new Kernel('prod', false);
        $container = new ContainerBuilder();
        $container->register('session.storage.factory.mock_file')->setArgument('$name', 'original');

        $kernel->process($container);

        self::assertSame('original', $container->getDefinition('session.storage.factory.mock_file')->getArgument('$name'));
    }
}
