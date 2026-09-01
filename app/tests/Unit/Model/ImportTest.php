<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Model;

use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Model\Import;

#[CoversClass(Import::class)]
final class ImportTest extends TestCase
{
    public function testStoresImportOptions(): void
    {
        $file = new PickedFile(base64_encode('remote/import.csv'));
        $import = (new Import())->setEnable(false)->setFile($file);

        self::assertFalse($import->isEnable());
        self::assertSame($file, $import->getFile());
    }
}
