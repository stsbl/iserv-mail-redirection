<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Validator\Constraints;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Validator\Constraints\Address;
use Stsbl\IServ\MailRedirection\Validator\Constraints\AddressValidator;
use Stsbl\IServ\MailRedirection\Validator\Constraints\LocalPart;
use Stsbl\IServ\MailRedirection\Validator\Constraints\LocalPartValidator;
use Stsbl\IServ\MailRedirection\Validator\Constraints\NotAccount;
use Stsbl\IServ\MailRedirection\Validator\Constraints\NotAccountValidator;
use Stsbl\IServ\MailRedirection\Validator\Constraints\SystemAddress;
use Stsbl\IServ\MailRedirection\Validator\Constraints\SystemAddressValidator;
use Symfony\Component\Validator\Constraint;

#[CoversClass(Address::class)]
#[CoversClass(LocalPart::class)]
#[CoversClass(NotAccount::class)]
#[CoversClass(SystemAddress::class)]
final class ConstraintTest extends TestCase
{
    public function testConstraintMetadataAndMessages(): void
    {
        $address = new Address();
        self::assertSame(AddressValidator::class, $address->validatedBy());
        self::assertSame(Constraint::CLASS_CONSTRAINT, $address->getTargets());
        self::assertNotSame('', $address->getDuplicateGroupMessage());
        self::assertNotSame('', $address->getDuplicateUserMessage());

        $localPart = new LocalPart();
        self::assertSame(LocalPartValidator::class, $localPart->validatedBy());
        self::assertSame(Constraint::PROPERTY_CONSTRAINT, $localPart->getTargets());
        self::assertNotSame('', $localPart->getMessage());
        self::assertNotSame('', $localPart->getMessageForAt());
        self::assertNotSame('', $localPart->getMessageForUmlauts());

        $notAccount = new NotAccount();
        self::assertSame(NotAccountValidator::class, $notAccount->validatedBy());
        self::assertSame(Constraint::PROPERTY_CONSTRAINT, $notAccount->getTargets());
        self::assertNotSame('', $notAccount->getUserMessage());
        self::assertNotSame('', $notAccount->getGroupMessage());
        self::assertNotSame('', $notAccount->getSystemAccountMessage());

        $systemAddress = new SystemAddress();
        self::assertSame(SystemAddressValidator::class, $systemAddress->validatedBy());
        self::assertSame(Constraint::PROPERTY_CONSTRAINT, $systemAddress->getTargets());
        self::assertNotSame('', $systemAddress->getMessage());
    }
}
