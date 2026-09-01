<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Validator\Constraints;

use IServ\Library\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Config\IServConfig;
use Stsbl\IServ\MailRedirection\Validator\Constraints\SystemAddress;
use Stsbl\IServ\MailRedirection\Validator\Constraints\SystemAddressValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<SystemAddressValidator> */
#[CoversClass(SystemAddressValidator::class)]
#[CoversClass(SystemAddress::class)]
final class SystemAddressValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidator
    {
        return new SystemAddressValidator(new IServConfig(new Config(['Domain' => 'example.test'])));
    }

    public function testRejectsSystemAddress(): void
    {
        $constraint = new SystemAddress();
        $this->validator->validate('root', $constraint);

        $this->buildViolation(sprintf($constraint->getMessage(), 'root@example.test'))->assertRaised();
    }

    public function testAllowsRegularAddress(): void
    {
        $this->validator->validate('helpdesk', new SystemAddress());
        $this->assertNoViolation();
    }
}
