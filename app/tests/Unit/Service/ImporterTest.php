<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Idm\RecipientUserDto;
use Stsbl\IServ\MailRedirection\Idm\RecipientGroupDto;
use Stsbl\IServ\MailRedirection\Model\Import;
use Stsbl\IServ\MailRedirection\Repository\AddressRepository;
use Stsbl\IServ\MailRedirection\Service\CsvFileReaderInterface;
use Stsbl\IServ\MailRedirection\Service\Importer;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(Importer::class)]
final class ImporterTest extends TestCase
{
    public function testImportsSelectedRemoteCsvFileWithoutLeakingState(): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "alias,\n");
        rewind($stream);

        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->method('getMimeType')->willReturn('text/csv');
        $reader->method('open')->willReturn($stream);

        $repository = $this->createMock(AddressRepository::class);
        $repository->method('findOneByRecipient')->with('alias')->willReturn(null);
        $repository->expects(self::once())->method('persist')->with(self::isInstanceOf(Address::class));
        $repository->expects(self::once())->method('flush');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $import = (new Import())
            ->setEnable(false)
            ->setFile(new PickedFile(base64_encode('remote/import.csv')));

        $result = (new Importer(
            $repository,
            $validator,
            $this->createMock(IdmUserFetcher::class),
            $this->createMock(IdmGroupFetcher::class),
            $reader,
        ))->import($import);

        self::assertSame([], $result->getWarnings());
        self::assertCount(1, $result->getNewAddresses());
        self::assertSame('alias', $result->getNewAddresses()[0]->getRecipient());
        self::assertFalse($result->getNewAddresses()[0]->getEnabled());
    }
    public function testImportsExistingAndNewAliasesWithResolvedRecipientsAndWarnings(): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "new,alice,teachers,New alias\nexisting,unknown,missing,Ignored note\n,alice,,\n");
        rewind($stream);
        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->method('getMimeType')->willReturn('text/plain');
        $reader->method('open')->willReturn($stream);
        $existing = (new Address())->setRecipient('existing')->setComment('Existing');
        $repository = $this->createMock(AddressRepository::class);
        $repository->method('findOneByRecipient')->willReturnCallback(static fn(string $recipient): ?Address => $recipient === 'existing' ? $existing : null);
        $repository->expects(self::atLeastOnce())->method('persist');
        $repository->expects(self::exactly(2))->method('flush');
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $users = $this->createMock(IdmUserFetcher::class);
        $users->method('getFilteredUsers')->willReturnCallback(static function (array $query): array {
            if ($query['user'] !== 'alice') {
                return [];
            }
            return ['alice' => new RecipientUserDto('0fd3d1ec-5146-47ef-8c11-70f23850dd8e', 'alice', 'Alice', 'Example', null, null)];
        });
        $groups = $this->createMock(IdmGroupFetcher::class);
        $groups->method('getFilteredGroups')->willReturnCallback(static function (array $query): array {
            if ($query['group'] !== 'teachers') {
                return [];
            }
            return ['teachers' => new RecipientGroupDto('10d3d1ec-5146-47ef-8c11-70f23850dd8e', 'teachers', 'Teachers', null)];
        });
        $import = (new Import())->setFile(new PickedFile(base64_encode('remote/import.csv')));

        $result = (new Importer($repository, $validator, $users, $groups, $reader))->import($import);

        self::assertCount(1, $result->getNewAddresses());
        self::assertSame('New alias', $result->getNewAddresses()[0]->getComment());
        self::assertCount(1, $result->getNewAddresses()[0]->getUsers());
        self::assertCount(1, $result->getNewAddresses()[0]->getGroups());
        self::assertCount(4, $result->getWarnings());
    }

}
