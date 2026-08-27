<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Entity\Address;
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
}
