<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Functional\Controller;

use IServ\Bundle\TestBrowser\Test\TestBrowser;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Controller\RedirectController;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(RedirectController::class)]
final class RedirectControllerTest extends WebTestCase
{
    public function testRedirectsLegacyAdministrationUrl(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailaliases');

        self::assertResponseStatusCodeSame(301);
        self::assertResponseRedirects('/admin/mailalias');
    }
}
