<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Idm;

use IServ\Bundle\IdmDataBroker\Dto\Attribute\AsIdmDto;
use IServ\Bundle\IdmDataBroker\Dto\Attribute\IdmField;
use IServ\Bundle\IdmDataBroker\Dto\IdmCacheScope;
use IServ\Library\Uuid\Uuid;
use IServ\Library\Uuid\UuidInterface;
use IServ\Library\Zeit\Zeit;

#[AsIdmDto(versions: [self::VERSION], scope: IdmCacheScope::User, ttl: AsIdmDto::TTL_ONE_DAY)]
final readonly class RecipientUserDto
{
    public const VERSION = 1;

    public function __construct(
        #[IdmField('hexUuid')]
        /** @internal Data Broker transport field; use $uuid in business logic. */
        public string $transportUuid,
        #[IdmField('user')]
        public ?string $account,
        #[IdmField('firstname')]
        public ?string $firstname,
        #[IdmField('lastname')]
        public ?string $lastname,
        #[IdmField('deleted')]
        /** @internal Data Broker transport field; use $deleted in business logic. */
        public ?string $transportDeleted,
        #[IdmField('auxInfo')]
        public ?string $auxInfo,
    ) {
        $this->uuid = Uuid::createFromNormalized($this->transportUuid);
        $this->deleted = $this->transportDeleted === null ? null : Zeit::create($this->transportDeleted);
    }

    public UuidInterface $uuid;

    public ?\DateTimeImmutable $deleted;

    public function getName(): string
    {
        return trim(sprintf('%s %s', $this->firstname ?? '', $this->lastname ?? ''));
    }
}
