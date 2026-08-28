<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Validator\Constraints\Address;
use Stsbl\MailAliasBundle\Validator\Constraints\LocalPart;
use Stsbl\MailAliasBundle\Validator\Constraints\NotAccount;
use Stsbl\MailAliasBundle\Validator\Constraints\SystemAddress;

final class AddressTest extends TestCase
{
    /**
     * @dataProvider provideConstraints
     *
     * @param class-string $constraint
     */
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
