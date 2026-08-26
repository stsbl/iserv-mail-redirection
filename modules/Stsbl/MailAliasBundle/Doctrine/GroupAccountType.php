<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;
use Stsbl\MailAliasBundle\Domain\GroupAccount;

final class GroupAccountType extends TextType
{
    public const NAME = 'stsbl_mail_alias_group_account';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?GroupAccount
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \LogicException(sprintf('Expected a group account string, got %s.', get_debug_type($value)));
        }

        return GroupAccount::import($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof GroupAccount) {
            throw new \LogicException(sprintf('Expected %s, got %s.', GroupAccount::class, get_debug_type($value)));
        }

        return $value->export();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
