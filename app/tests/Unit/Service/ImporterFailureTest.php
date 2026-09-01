<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Exception\ImportException;
use Stsbl\IServ\MailRedirection\Model\Import;
use Stsbl\IServ\MailRedirection\Repository\AddressRepository;
use Stsbl\IServ\MailRedirection\Service\CsvFileReaderInterface;
use Stsbl\IServ\MailRedirection\Service\Importer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(Importer::class)]
final class ImporterFailureTest extends TestCase
{
    public function testRejectsImportWithoutFile(): void
    {
        $this->expectExceptionObject(ImportException::fileIsNull());
        $this->importer($this->createMock(CsvFileReaderInterface::class))->import(new Import());
    }

    public function testRejectsNonCsvMimeType(): void
    {
        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->method('getMimeType')->willReturn('application/pdf');
        $import = (new Import())->setFile(new PickedFile(base64_encode('remote/file.pdf')));

        $this->expectExceptionObject(ImportException::invalidMimeType());
        $this->importer($reader)->import($import);
    }

    public function testReportsInvalidColumnCountWithLineNumber(): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "only-one-column\n");
        rewind($stream);
        $reader = $this->createMock(CsvFileReaderInterface::class);
        $reader->method('getMimeType')->willReturn('text/csv');
        $reader->method('open')->willReturn($stream);
        $import = (new Import())->setFile(new PickedFile(base64_encode('remote/file.csv')));

        try {
            $this->importer($reader)->import($import);
            self::fail('Expected an import failure.');
        } catch (ImportException $exception) {
            self::assertSame(ImportException::MESSAGE_INVALID_COLUMN_AMOUNT, $exception->getMessage());
            self::assertSame(1, $exception->getFileLine());
            self::assertSame(1, $exception->getColumnAmount());
            self::assertSame(2, $exception->getExpected());
        }
    }

    private function importer(CsvFileReaderInterface $reader): Importer
    {
        return new Importer(
            $this->createMock(AddressRepository::class),
            $this->createMock(ValidatorInterface::class),
            $this->createMock(IdmUserFetcher::class),
            $this->createMock(IdmGroupFetcher::class),
            $reader,
        );
    }
}
