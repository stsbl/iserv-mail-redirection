<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Model\ImportResult;

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
