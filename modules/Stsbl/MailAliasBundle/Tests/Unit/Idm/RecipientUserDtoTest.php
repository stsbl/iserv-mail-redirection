<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Idm;

use PHPUnit\Framework\TestCase;
use IServ\Library\Uuid\UuidInterface;
use IServ\Bundle\IdmDataBroker\Dto\IdmDtoMetadataExtractor;
use Stsbl\MailAliasBundle\Idm\RecipientUserDto;

final class RecipientUserDtoTest extends TestCase
{
    public function testFormatsRecipientName(): void
    {
        $dto = new RecipientUserDto('d7dcc25b-0303-43b2-b350-e400338ea223', 'max', 'Max', 'Mustermann', '2026-08-26T12:00:00+00:00', null);

        self::assertSame('Max Mustermann', $dto->getName());
        self::assertInstanceOf(UuidInterface::class, $dto->uuid);
        self::assertInstanceOf(\DateTimeImmutable::class, $dto->deleted);
        self::assertSame('2026-08-26T12:00:00+00:00', $dto->deleted?->format(DATE_ATOM));
    }

    public function testRequestsTheUuidTransportAttribute(): void
    {
        $metadata = IdmDtoMetadataExtractor::extract(RecipientUserDto::class);

        self::assertContains('hexUuid', $metadata['attributes']);
        self::assertNotContains('transportUuid', $metadata['attributes']);
    }
}
