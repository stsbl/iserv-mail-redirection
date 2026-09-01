<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Web\Controller;

use IServ\Bundle\TestBrowser\Test\TestBrowser;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Controller\MailAliasAutocompleteController;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(MailAliasAutocompleteController::class)]
final class MailAliasRecipientsControllerTest extends WebTestCase
{
    public function testMailAliasAdministratorGetsRecipientSuggestions(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $idm = $this->createMock(IdmClientInterface::class);
        $idm->expects($this->atLeast(2))->method('performRequest')->willReturnCallback(static function (string $method, string $url): array {
            if (str_starts_with($url, 'iserv/idm/api/v1/lookup/groups?')) {
                return ['exact' => [['group' => 'teachers', 'name' => 'Teachers']]];
            }

            return ['partial' => [[
                'user' => 'teacher.one',
                'firstname' => 'Tea',
                'lastname' => 'Cher',
                'auxInfo' => 'Teacher',
                'hexUuid' => '0fd3d1ec-5146-47ef-8c11-70f23850dd8e',
            ]]];
        });
        $avatars = $this->createMock(AvatarRendererInterface::class);
        $avatars->expects($this->once())->method('renderPlaceholder')->willReturn('<span class="group-avatar">T</span>');
        $avatars->expects($this->once())->method('render')->willReturn('<img class="user-avatar">');

        self::getContainer()->set(IdmClientInterface::class, $idm);
        self::getContainer()->set(AvatarRendererInterface::class, $avatars);
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/recipients?type=group,user&query=tea');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[
            {"label":"Teachers","text":"Teachers","value":"group:teachers","source":"group","avatarHtml":"<span class=\\"group-avatar\\">T</span>","icon":"fa-users","extra":"Group","certainty":10,"fuzzy":false,"expandable":false,"readonly":false},
            {"label":"Tea Cher","text":"Tea Cher","value":"user:teacher.one","source":"user","avatarHtml":"<img class=\\"user-avatar\\">","icon":"fa-user","extra":"Teacher","certainty":5,"fuzzy":false,"expandable":false,"readonly":false}
        ]', (string) $client->getResponse()->getContent());
    }

    public function testRejectsRecipientLookupWithoutMailAliasPrivilege(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->loginAdmin(TestUserBuilder::create()->getUser());
        $client->request('GET', '/admin/mailalias/recipients?type=user&query=tea');

        self::assertResponseStatusCodeSame(403);
    }
}
