<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;
use Stsbl\IServ\MailRedirection\Idm\RecipientGroupDto;
use Stsbl\IServ\MailRedirection\Idm\RecipientUserDto;
use Stsbl\IServ\MailRedirection\Repository\GroupRecipientRepository;
use Stsbl\IServ\MailRedirection\Repository\UserRecipientRepository;

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
