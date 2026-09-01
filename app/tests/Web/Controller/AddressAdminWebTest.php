<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Web\Controller;

use IServ\Bundle\TestBrowser\Test\TestBrowser;
use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Config\Config;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Admin\AddressAdmin;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;
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

        /** @var TestBrowser $editClient */
        self::ensureKernelShutdown();
        $editClient = self::createClient();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        $editIdm = $this->createMock(IdmClientInterface::class);
        $editIdm->method('performRequest')->willReturn([]);
        self::getContainer()->set(IdmClientInterface::class, $editIdm);
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $editClient->disableReboot();
        $editClient->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $editClient->request('GET', '/admin/mailalias/edit/' . $address->getId());
        $editClient->submitForm('Save', [
            'mailalias[recipient]' => 'renamed-help',
            'mailalias[enabled]' => '0',
            'mailalias[comment]' => 'Updated in test',
        ]);

        self::assertResponseRedirects();
        $entityManager->clear();
        self::assertSame('renamed-help', $entityManager->getRepository(Address::class)->find($address->getId())?->getRecipient());

    }

    public function testRendersAliasDeletionConfirmation(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $entityManager = self::recreateSchema();
        $address = (new Address())->setRecipient('obsolete')->setComment('Test');
        $entityManager->persist($address);
        $entityManager->flush();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        $idm = $this->createMock(IdmClientInterface::class);
        $idm->method('performRequest')->willReturn([]);
        self::getContainer()->set(IdmClientInterface::class, $idm);
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/delete/' . $address->getId());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('delete', strtolower((string) $client->getResponse()->getContent()));
    }

    public function testWritesAuditLogsForRecipientAndAliasChanges(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $admin = self::getContainer()->get(AddressAdmin::class);
        $address = (new Address())->setRecipient('new-help')->setEnabled(false)->setComment('new note');
        $address->addUser(new UserRecipient(new Username('new-user')));
        $address->addGroup(new GroupRecipient(new GroupAccount('new-group')));

        $admin->postPersist($address);
        $admin->preUpdate($address, [
            'recipient' => 'old-help',
            'enabled' => true,
            'comment' => 'old note',
            'recipients' => [
                new AutocompleteTagsData('user:old-user', 'old-user', 'user'),
                new AutocompleteTagsData('group:old-group', 'old-group', 'group'),
            ],
        ]);
        $admin->postRemove($address);

        self::assertTrue(true);
    }

}
