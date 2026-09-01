<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Admin\AddressAdmin;
use Stsbl\IServ\MailRedirection\Config\IServConfig;
use IServ\Bundle\AdminIntegration\Menu\AdminBreadcrumbsInterface;

#[CoversClass(AddressAdmin::class)]
final class AddressAdminTest extends TestCase
{
    public function testProvidesCsvImportInstructions(): void
    {
        self::assertStringContainsString('CSV', AddressAdmin::getImportExplanation());
        self::assertSame([
            'Original recipient (Only local part, without the @ and the domain)',
            'Users (Account names as a comma separated list, can be empty)',
            'Groups (Account names as a comma separated list, can be empty)',
            'Note (optional)',
        ], AddressAdmin::getImportExplanationFieldList());
    }

    public function testDeclaresItsAdditionalSubscribedServices(): void
    {
        $services = AddressAdmin::getSubscribedServices();

        self::assertContains(IServConfig::class, $services);
        self::assertContains(AdminBreadcrumbsInterface::class, $services);
    }
}
