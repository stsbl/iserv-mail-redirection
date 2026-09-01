<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;
use Stsbl\IServ\MailRedirection\Idm\RecipientGroupDto;
use Stsbl\IServ\MailRedirection\Idm\RecipientUserDto;
use Stsbl\IServ\MailRedirection\Repository\GroupRecipientRepository;
use Stsbl\IServ\MailRedirection\Repository\UserRecipientRepository;
use Stsbl\IServ\MailRedirection\Service\RecipientIdmSynchronizer;

#[CoversClass(RecipientIdmSynchronizer::class)]
final class RecipientIdmSynchronizerTest extends TestCase
{
    public function testBackfillsUuidsAndUpdatesRenamedAccounts(): void
    {
        $userRecipient = new UserRecipient(new Username('old-user'));
        $groupRecipient = new GroupRecipient(new GroupAccount('old-group'));
        $user = new RecipientUserDto('d7dcc25b-0303-43b2-b350-e400338ea223', 'new-user', 'New', 'User', null, null);
        $group = new RecipientGroupDto('dfdcc25b-0303-43b2-b350-e400338ea223', 'new-group', 'New group', null);

        $users = $this->createMock(UserRecipientRepository::class);
        $users->method('all')->willReturn([$userRecipient]);
        $users->expects(self::once())->method('flush');
        $groups = $this->createMock(GroupRecipientRepository::class);
        $groups->method('all')->willReturn([$groupRecipient]);
        $groups->expects(self::once())->method('flush');
        $idmUsers = $this->createMock(IdmUserFetcher::class);
        $idmUsers->expects(self::once())->method('getFilteredUsers')->with(['user' => 'old-user'], RecipientUserDto::class)->willReturn([$user]);
        $idmGroups = $this->createMock(IdmGroupFetcher::class);
        $idmGroups->expects(self::once())->method('getFilteredGroups')->with(['group' => 'old-group'], RecipientGroupDto::class)->willReturn([$group]);

        $result = (new RecipientIdmSynchronizer($users, $groups, $idmUsers, $idmGroups))->sync();

        self::assertSame(['users' => 1, 'groups' => 1], $result);
        self::assertSame('new-user', (string) $userRecipient->getUsername());
        self::assertSame($user->uuid->toNormalizedString(), $userRecipient->getUuid()?->toNormalizedString());
        self::assertSame('new-group', (string) $groupRecipient->getAccount());
        self::assertSame($group->uuid->toNormalizedString(), $groupRecipient->getUuid()?->toNormalizedString());
    }

    public function testKeepsRecipientsWhenIdmCannotResolveAnAccount(): void
    {
        $userRecipient = new UserRecipient(new Username('missing-user'));
        $groupRecipient = new GroupRecipient(new GroupAccount('missing-group'));
        $users = $this->createMock(UserRecipientRepository::class);
        $users->method('all')->willReturn([$userRecipient]);
        $users->expects(self::once())->method('flush');
        $groups = $this->createMock(GroupRecipientRepository::class);
        $groups->method('all')->willReturn([$groupRecipient]);
        $groups->expects(self::once())->method('flush');
        $idmUsers = $this->createMock(IdmUserFetcher::class);
        $idmUsers->method('getFilteredUsers')->willReturn([]);
        $idmGroups = $this->createMock(IdmGroupFetcher::class);
        $idmGroups->method('getFilteredGroups')->willReturn([]);

        self::assertSame(['users' => 0, 'groups' => 0], (new RecipientIdmSynchronizer($users, $groups, $idmUsers, $idmGroups))->sync());
    }
}
