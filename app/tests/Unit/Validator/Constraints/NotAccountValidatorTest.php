<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Validator\Constraints;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\Library\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Config\IServConfig;
use Stsbl\IServ\MailRedirection\Idm\RecipientUserDto;
use Stsbl\IServ\MailRedirection\Idm\RecipientGroupDto;
use Stsbl\IServ\MailRedirection\Validator\Constraints\NotAccount;
use Stsbl\IServ\MailRedirection\Validator\Constraints\NotAccountValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<NotAccountValidator> */
#[CoversClass(NotAccountValidator::class)]
final class NotAccountValidatorTest extends ConstraintValidatorTestCase
{
    private IdmUserFetcher&MockObject $users;
    private IdmGroupFetcher&MockObject $groups;

    protected function createValidator(): ConstraintValidator
    {
        $this->users = $this->createMock(IdmUserFetcher::class);
        $this->groups = $this->createMock(IdmGroupFetcher::class);

        return new NotAccountValidator(new IServConfig(new Config(['Domain' => 'example.test'])), $this->groups, $this->users);
    }

    public function testDeletedUserStillReservesTheAlias(): void
    {
        $deletedUser = new RecipientUserDto(
            'd7dcc25b-0303-43b2-b350-e400338ea223',
            'former-user',
            'Former',
            'User',
            '2026-08-26T12:00:00+00:00',
            null,
        );
        $this->users->expects(self::once())
            ->method('getFilteredUsers')
            ->with(['user' => 'former-user'], RecipientUserDto::class)
            ->willReturn(['d7dcc25b-0303-43b2-b350-e400338ea223' => $deletedUser]);
        $this->groups->expects(self::never())->method('getFilteredGroups');

        $constraint = new NotAccount();
        $this->validator->validate('former-user', $constraint);

        $this->buildViolation(sprintf($constraint->getUserMessage(), 'former-user@example.test'))->assertRaised();
    }

    public function testDeletedGroupStillReservesTheAlias(): void
    {
        $deletedGroup = new RecipientGroupDto(
            'd7dcc25b-0303-43b2-b350-e400338ea223',
            'former-group',
            'Former group',
            '2026-08-26T12:00:00+00:00',
        );
        $this->users->expects(self::once())
            ->method('getFilteredUsers')
            ->with(['user' => 'former-group'], RecipientUserDto::class)
            ->willReturn([]);
        $this->groups->expects(self::once())
            ->method('getFilteredGroups')
            ->with(['group' => 'former-group'], RecipientGroupDto::class)
            ->willReturn(['d7dcc25b-0303-43b2-b350-e400338ea223' => $deletedGroup]);

        $constraint = new NotAccount();
        $this->validator->validate('former-group', $constraint);

        $this->buildViolation(sprintf($constraint->getGroupMessage(), 'former-group@example.test'))->assertRaised();
    }

    public function testIgnoresNullValues(): void
    {
        $this->users->expects(self::never())->method('getFilteredUsers');
        $this->groups->expects(self::never())->method('getFilteredGroups');
        $this->validator->validate(null, new NotAccount());
        $this->assertNoViolation();
    }

    public function testRejectsAnUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('alias', new NotBlank());
    }

    public function testRejectsSystemAccounts(): void
    {
        $this->users->method('getFilteredUsers')->willReturn([]);
        $this->groups->method('getFilteredGroups')->willReturn([]);
        $constraint = new NotAccount();

        $this->validator->validate('root', $constraint);

        $this->buildViolation(sprintf($constraint->getSystemAccountMessage(), 'root'))->assertRaised();
    }
}
