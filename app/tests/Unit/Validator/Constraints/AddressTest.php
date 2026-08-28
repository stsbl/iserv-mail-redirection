<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Stsbl\IServ\MailRedirection\Validator\Constraints\Address;
use Stsbl\IServ\MailRedirection\Validator\Constraints\LocalPart;
use Stsbl\IServ\MailRedirection\Validator\Constraints\NotAccount;
use Stsbl\IServ\MailRedirection\Validator\Constraints\SystemAddress;

#[CoversClass(Address::class)]
final class AddressTest extends TestCase
{
    /** @param class-string $constraint */
    #[DataProvider('provideConstraints')]
    public function testCanBeUsedAsAnAttribute(string $constraint, int $target): void
    {
        $attribute = (new \ReflectionClass($constraint))->getAttributes(\Attribute::class)[0] ?? null;

        self::assertNotNull($attribute);
        self::assertSame($target, $attribute->newInstance()->flags);
    }

    /** @return iterable<string, array{class-string, int}> */
    public static function provideConstraints(): iterable
    {
        yield 'address' => [Address::class, \Attribute::TARGET_CLASS];
        yield 'system address' => [SystemAddress::class, \Attribute::TARGET_PROPERTY];
        yield 'local part' => [LocalPart::class, \Attribute::TARGET_PROPERTY];
        yield 'not account' => [NotAccount::class, \Attribute::TARGET_PROPERTY];
    }
}
