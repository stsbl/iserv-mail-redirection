<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Web\Controller;

use IServ\Bundle\TestBrowser\Test\TestBrowser;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Config\Config;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Admin\AddressAdmin;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Doctrine\ORM\EntityManagerInterface;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Stsbl\IServ\MailRedirection\Tests\Common\DatabaseSchemaTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(AddressAdmin::class)]
final class AddressAdminWebTest extends WebTestCase
{
    use DatabaseSchemaTrait;

    public function testRendersMailAliasAdministrationIndex(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $entityManager = self::recreateSchema();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        $idm = $this->createMock(IdmClientInterface::class);
        $idm->method('performRequest')->willReturn([]);
        self::getContainer()->set(IdmClientInterface::class, $idm);
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Original recipient', (string) $client->getResponse()->getContent());
    }
    public function testRendersAddFormWithUnifiedRecipientAutocomplete(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $entityManager = self::recreateSchema();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        $idm = $this->createMock(IdmClientInterface::class);
        $idm->method('performRequest')->willReturn([]);
        self::getContainer()->set(IdmClientInterface::class, $idm);
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/add');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Recipients', (string) $client->getResponse()->getContent());
    }

    public function testRendersAliasShowPage(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::recreateSchema();
        $address = (new Address())->setRecipient('help')->setComment('Test');
        $entityManager->persist($address);
        $entityManager->flush();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/show/' . $address->getId());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('help', (string) $client->getResponse()->getContent());
    }

    public function testAppliesAdministrationFiltersAndRendersEditPage(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::recreateSchema();
        $address = (new Address())->setRecipient('help')->setComment('Test');
        $entityManager->persist($address);
        $entityManager->flush();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());

        foreach (['without', 'without-users', 'without-groups'] as $association) {
            $client->request('GET', '/admin/mailalias?filter[associations]=' . $association . '&filter[enabled]=true&filter[recipient]=help');
            self::assertResponseIsSuccessful();
        }
        $client->request('GET', '/admin/mailalias/edit/' . $address->getId());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Recipients', (string) $client->getResponse()->getContent());
    }

    public function testCreatesAnAliasThroughTheAdministrationForm(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $entityManager = self::recreateSchema();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        $idm = $this->createMock(IdmClientInterface::class);
        $idm->method('performRequest')->willReturn([]);
        self::getContainer()->set(IdmClientInterface::class, $idm);
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/add');
        $client->submitForm('Save', [
            'mailalias[recipient]' => 'new-help',
            'mailalias[enabled]' => '1',
            'mailalias[comment]' => 'Created in test',
        ]);

        self::assertResponseRedirects();
        $address = $entityManager->getRepository(Address::class)->findOneBy(['recipient' => 'new-help']);
        self::assertSame('new-help', $address?->getRecipient());
        self::assertNotNull($address?->getId());

        $client->request('GET', '/admin/mailalias/edit/' . $address->getId());
        $client->submitForm('Save', [
            'mailalias[recipient]' => 'renamed-help',
            'mailalias[enabled]' => '0',
            'mailalias[comment]' => 'Updated in test',
        ]);

        self::assertResponseRedirects();

    }

}
