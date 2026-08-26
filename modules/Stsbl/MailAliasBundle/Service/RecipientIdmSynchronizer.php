<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\Library\User\User\Username;
use Stsbl\MailAliasBundle\Entity\GroupRecipient;
use Stsbl\MailAliasBundle\Domain\GroupAccount;
use Stsbl\MailAliasBundle\Entity\UserRecipient;
use Stsbl\MailAliasBundle\Idm\RecipientGroupDto;
use Stsbl\MailAliasBundle\Idm\RecipientUserDto;
use Stsbl\MailAliasBundle\Repository\GroupRecipientRepository;
use Stsbl\MailAliasBundle\Repository\UserRecipientRepository;

final class RecipientIdmSynchronizer
{
    public function __construct(
        private readonly UserRecipientRepository $users,
        private readonly GroupRecipientRepository $groups,
        private readonly IdmUserFetcher $idmUsers,
        private readonly IdmGroupFetcher $idmGroups,
    ) {
    }

    /** @return array{users: int, groups: int} */
    public function sync(): array
    {
        $updatedUsers = 0;
        foreach ($this->users->all() as $recipient) {
            if ($this->syncUser($recipient)) {
                ++$updatedUsers;
            }
        }
        $this->users->flush();

        $updatedGroups = 0;
        foreach ($this->groups->all() as $recipient) {
            if ($this->syncGroup($recipient)) {
                ++$updatedGroups;
            }
        }
        $this->groups->flush();

        return ['users' => $updatedUsers, 'groups' => $updatedGroups];
    }

    private function syncUser(UserRecipient $recipient): bool
    {
        $user = $recipient->getUuid() === null
            ? current($this->idmUsers->getFilteredUsers(['user' => (string) $recipient->getUsername()], RecipientUserDto::class))
            : $this->idmUsers->getUser($recipient->getUuid(), RecipientUserDto::class);

        if (!$user instanceof RecipientUserDto || $user->account === null || $user->account === '') {
            return false;
        }

        $changed = false;
        if ($recipient->getUuid()?->toNormalizedString() !== $user->uuid->toNormalizedString()) {
            $recipient->setUuid($user->uuid);
            $changed = true;
        }
        if ((string) $recipient->getUsername() !== $user->account) {
            $recipient->setUsername(new Username($user->account));
            $changed = true;
        }

        return $changed;
    }

    private function syncGroup(GroupRecipient $recipient): bool
    {
        $group = $recipient->getUuid() === null
            ? current($this->idmGroups->getFilteredGroups(['group' => (string) $recipient->getAccount()], RecipientGroupDto::class))
            : $this->idmGroups->getGroup($recipient->getUuid(), RecipientGroupDto::class);

        if (!$group instanceof RecipientGroupDto || $group->account === null || $group->account === '') {
            return false;
        }

        $changed = false;
        if ($recipient->getUuid()?->toNormalizedString() !== $group->uuid->toNormalizedString()) {
            $recipient->setUuid($group->uuid);
            $changed = true;
        }
        if ((string) $recipient->getAccount() !== $group->account) {
            $recipient->setAccount(new GroupAccount($group->account));
            $changed = true;
        }

        return $changed;
    }
}
