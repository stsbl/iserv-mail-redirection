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
use Stsbl\IServ\MailRedirection\Repository\AddressRepository;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Stsbl\IServ\MailRedirection\Service\CsvFileReaderInterface;
use Stsbl\IServ\MailRedirection\Service\Importer;
use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
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

    public function testSubmitsAnInvalidRemoteFile(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->expects(self::once())->method('getMimeType')->willReturn('application/pdf');
        $reader->expects(self::never())->method('open');
        $addresses = $this->createMock(AddressRepository::class);
        $addresses->expects(self::never())->method('persist');
        $addresses->expects(self::never())->method('flush');
        self::getContainer()->set(Importer::class, new Importer($addresses, $this->createMock(ValidatorInterface::class), $this->createMock(IdmUserFetcher::class), $this->createMock(IdmGroupFetcher::class), $reader));
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/import');
        $client->submitForm('Import', ['import[file]' => base64_encode('remote/file.pdf')]);

        self::assertResponseRedirects('/admin/mailalias');
    }

    public function testSubmitsAValidRemoteCsvFile(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "help,,\n");
        rewind($stream);
        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->expects(self::once())->method('getMimeType')->willReturn('text/csv');
        $reader->expects(self::once())->method('open')->willReturn($stream);
        $addresses = $this->createMock(AddressRepository::class);
        $addresses->method('findOneByRecipient')->willReturn(null);
        $addresses->expects(self::once())->method('persist');
        $addresses->expects(self::once())->method('flush');
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        self::getContainer()->set(Importer::class, new Importer($addresses, $validator, $this->createMock(IdmUserFetcher::class), $this->createMock(IdmGroupFetcher::class), $reader));
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/import');
        $client->submitForm('Import', ['import[file]' => base64_encode('remote/file.csv')]);

        self::assertResponseRedirects('/admin/mailalias');
    }

    public function testRejectsRemoteCsvWithAnInvalidColumnAmount(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "help\n");
        rewind($stream);
        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->method('getMimeType')->willReturn('text/csv');
        $reader->method('open')->willReturn($stream);
        self::getContainer()->set(Importer::class, new Importer(
            $this->createMock(AddressRepository::class),
            $this->createMock(ValidatorInterface::class),
            $this->createMock(IdmUserFetcher::class),
            $this->createMock(IdmGroupFetcher::class),
            $reader,
        ));
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
        $client->disableReboot();
        $client->loginAdmin(TestUserBuilder::create()->privilege(Privilege::ADMIN_UUID)->getUser());
        $client->request('GET', '/admin/mailalias/import');
        $client->submitForm('Import', ['import[file]' => base64_encode('remote/file.csv')]);

        self::assertResponseRedirects('/admin/mailalias');
    }
}
