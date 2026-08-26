<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Idm;

use IServ\Library\Uuid\UuidInterface;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Idm\RecipientGroupDto;

final class RecipientGroupDtoTest extends TestCase
{
    public function testExposesTypedIdentityAndDeletionDate(): void
    {
        $dto = new RecipientGroupDto('d7dcc25b-0303-43b2-b350-e400338ea223', 'teachers', 'Teachers', '2026-08-26T12:00:00+00:00');

        self::assertInstanceOf(UuidInterface::class, $dto->uuid);
        self::assertInstanceOf(\DateTimeImmutable::class, $dto->deleted);
        self::assertSame('2026-08-26T12:00:00+00:00', $dto->deleted?->format(DATE_ATOM));
    }
}
