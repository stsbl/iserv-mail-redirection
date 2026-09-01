<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Validator\Constraints;

use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;
use Stsbl\IServ\MailRedirection\Entity\Address as AddressEntity;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;
use Stsbl\IServ\MailRedirection\Validator\Constraints\Address;
use Stsbl\IServ\MailRedirection\Validator\Constraints\AddressValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<AddressValidator> */
#[CoversClass(AddressValidator::class)]
#[CoversClass(Address::class)]
final class AddressValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidator
    {
        return new AddressValidator();
    }

    public function testReportsDuplicateGroupRecipient(): void
    {
        $address = (new AddressEntity())->setRecipient('help');
        $address->addGroup(new GroupRecipient(new GroupAccount('teachers')));
        $address->addGroup(new GroupRecipient(new GroupAccount('teachers')));
        $constraint = new Address();

        $this->validator->validate($address, $constraint);

        $this->buildViolation(sprintf($constraint->getDuplicateGroupMessage(), 'teachers', 'help'))->atPath('property.path.recipient')->assertRaised();
    }

    public function testReportsDuplicateUserRecipient(): void
    {
        $address = (new AddressEntity())->setRecipient('help');
        $address->addUser(new UserRecipient(new Username('alice')));
        $address->addUser(new UserRecipient(new Username('alice')));
        $constraint = new Address();

        $this->validator->validate($address, $constraint);

        $this->buildViolation(sprintf($constraint->getDuplicateUserMessage(), 'alice', 'help'))->atPath('property.path.recipient')->assertRaised();
    }

}
