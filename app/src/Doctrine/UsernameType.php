<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;
use Stsbl\IServ\MailRedirection\Domain\Username;

final class UsernameType extends TextType
{
    public const NAME = 'stsbl_mail_alias_username';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Username
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \LogicException(sprintf('Expected a username string, got %s.', get_debug_type($value)));
        }

        return Username::import($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Username) {
            throw new \LogicException(sprintf('Expected %s, got %s.', Username::class, get_debug_type($value)));
        }

        return $value->export();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
