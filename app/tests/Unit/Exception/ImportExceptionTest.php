<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Exception\ImportException;

#[CoversClass(ImportException::class)]
final class ImportExceptionTest extends TestCase
{
    public function testCreatesTypedImportFailures(): void
    {
        $mime = ImportException::invalidMimeType();
        $columns = ImportException::invalidColumnAmount(7, 2, 4);
        $file = ImportException::fileIsNull();

        self::assertSame(ImportException::MESSAGE_INVALID_MIME_TYPE, $mime->getMessage());
        self::assertSame(ImportException::MESSAGE_INVALID_COLUMN_AMOUNT, $columns->getMessage());
        self::assertSame(7, $columns->getFileLine());
        self::assertSame(2, $columns->getColumnAmount());
        self::assertSame(4, $columns->getExpected());
        self::assertSame(ImportException::MESSAGE_FILE_IS_NULL, $file->getMessage());
    }
}
