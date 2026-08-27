<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Model\ImportResult;

#[CoversClass(ImportResult::class)]
final class ImportResultTest extends TestCase
{
    public function testKeepsImportOutputOutOfTheImporterService(): void
    {
        $address = new Address();
        $result = new ImportResult([$address], ['First warning']);

        self::assertSame([$address], $result->getNewAddresses());
        self::assertSame(['First warning'], $result->getWarnings());
    }
}
