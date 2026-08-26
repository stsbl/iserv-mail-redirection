<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\Library\User\User\Username;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Entity\GroupRecipient;
use Stsbl\MailAliasBundle\Domain\GroupAccount;
use Stsbl\MailAliasBundle\Entity\UserRecipient;
use Stsbl\MailAliasBundle\Idm\RecipientGroupDto;
use Stsbl\MailAliasBundle\Idm\RecipientUserDto;
use Stsbl\MailAliasBundle\Repository\GroupRecipientRepository;
use Stsbl\MailAliasBundle\Repository\UserRecipientRepository;
use Stsbl\MailAliasBundle\Service\RecipientIdmSynchronizer;

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
}
