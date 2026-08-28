<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Service;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\Library\Avatar\AvatarSize;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Avatar\Renderer\AvatarRenderStyle;
use IServ\Library\Avatar\UrlGenerator\AvatarPlaceholderStyle;
use Stsbl\IServ\MailRedirection\Idm\RecipientGroupDto;
use Stsbl\IServ\MailRedirection\Idm\RecipientUserDto;
use Stsbl\IServ\MailRedirection\View\RecipientCard;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class RecipientCardResolver
{
    /** Global Administrator role UUID from user-backend/roles/user-backend.yml. */
    private const ADMIN_ROLE = 'ROLE_f66aee04-c335-4299-9cfe-ca7176cc0213';

    public function __construct(
        private IdmUserFetcher $users,
        private IdmGroupFetcher $groups,
        private AvatarRendererInterface $avatars,
        private AuthorizationCheckerInterface $authorization,
    ) {
    }

    /** @param iterable<AutocompleteTagsData> $recipients
     *  @return list<RecipientCard> */
    public function resolve(iterable $recipients): array
    {
        $cards = [];
        foreach ($recipients as $recipient) {
            $account = $recipient->getId() ?? (string) $recipient;
            $card = $recipient->getSource() === 'group'
                ? $this->group($account)
                : $this->user($account);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        usort($cards, static fn(RecipientCard $left, RecipientCard $right): int => strnatcasecmp($left->name, $right->name));

        return $cards;
    }

    private function user(string $account): ?RecipientCard
    {
        $user = current($this->users->getFilteredUsers(['user' => $account], RecipientUserDto::class));
        if (!$user instanceof RecipientUserDto) {
            return null;
        }

        $name = $user->getName() ?: ($user->account ?? $account);
        $link = $this->isMailAliasAdministrator()
            ? '/iserv/admin/user/show/' . rawurlencode($user->account ?? $account)
            : '/iserv/account/profile/' . $user->uuid->toNormalizedString();

        return new RecipientCard(
            $name,
            $user->account ?? $account,
            $this->avatars->render($user->uuid, AvatarSize::default(), AvatarRenderStyle::ROUNDED, $name),
            $link,
        );
    }

    private function group(string $account): ?RecipientCard
    {
        $group = current($this->groups->getFilteredGroups(['group' => $account], RecipientGroupDto::class));
        if (!$group instanceof RecipientGroupDto) {
            return null;
        }

        $name = $group->name !== '' ? $group->name : ($group->account ?? $account);
        $link = $this->isMailAliasAdministrator()
            ? '/iserv/admin/group/show/' . rawurlencode($group->account ?? $account)
            : null;

        return new RecipientCard(
            $name,
            $group->account ?? $account,
            $this->avatars->renderPlaceholder($name, AvatarSize::default(), AvatarRenderStyle::CIRCLE, AvatarPlaceholderStyle::GROUP),
            $link,
        );
    }

    private function isMailAliasAdministrator(): bool
    {
        return $this->authorization->isGranted(self::ADMIN_ROLE);
    }
}
