<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Validator\Constraints;

use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Validator\Constraints\LocalPart;
use Stsbl\IServ\MailRedirection\Validator\Constraints\LocalPartValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<LocalPartValidator> */
#[CoversClass(LocalPartValidator::class)]
#[CoversClass(LocalPart::class)]
final class LocalPartValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidator
    {
        return new LocalPartValidator();
    }

    public function testAcceptsValidLocalPart(): void
    {
        $this->validator->validate('support.team', new LocalPart());
        $this->assertNoViolation();
    }

    public function testReportsAtSign(): void
    {
        $constraint = new LocalPart();
        $this->validator->validate('support@example.test', $constraint);
        $this->buildViolation($constraint->getMessageForAt())->assertRaised();
    }

    public function testReportsUmlaut(): void
    {
        $constraint = new LocalPart();
        $this->validator->validate('grüppe', $constraint);
        $this->buildViolation($constraint->getMessageForUmlauts())->assertRaised();
    }
}
