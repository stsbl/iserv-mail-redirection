<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Web\Controller;

use IServ\Bundle\TestBrowser\Test\TestBrowser;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Config\Config;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Controller\MailAliasImportController;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(MailAliasImportController::class)]
final class MailAliasImportWebTest extends WebTestCase
{
    public function testRendersRemoteCsvImportForm(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/import');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Enable new aliases', (string) $client->getResponse()->getContent());
    }
}
