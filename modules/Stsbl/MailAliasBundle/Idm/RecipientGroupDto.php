<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Idm;

use IServ\Bundle\IdmDataBroker\Dto\Attribute\AsIdmDto;
use IServ\Bundle\IdmDataBroker\Dto\Attribute\IdmField;
use IServ\Bundle\IdmDataBroker\Dto\IdmCacheScope;
use IServ\Library\Uuid\Uuid;
use IServ\Library\Uuid\UuidInterface;
use IServ\Library\Zeit\Zeit;

#[AsIdmDto(versions: [self::VERSION], scope: IdmCacheScope::Group, ttl: AsIdmDto::TTL_ONE_DAY)]
final readonly class RecipientGroupDto
{
    public const VERSION = 1;

    public function __construct(
        #[IdmField('hexUuid')]
        /** @internal Data Broker transport field; use $uuid in business logic. */
        public string $transportUuid,
        #[IdmField('group')]
        public ?string $account,
        #[IdmField('name')]
        public string $name,
        #[IdmField('deleted')]
        /** @internal Data Broker transport field; use $deleted in business logic. */
        public ?string $transportDeleted,
    ) {
        $this->uuid = Uuid::createFromNormalized($this->transportUuid);
        $this->deleted = $this->transportDeleted === null ? null : Zeit::create($this->transportDeleted);
    }

    public UuidInterface $uuid;

    public ?\DateTimeImmutable $deleted;
}
