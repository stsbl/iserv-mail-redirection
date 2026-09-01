<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Service;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Idm\RecipientGroupDto;
use Stsbl\IServ\MailRedirection\Idm\RecipientUserDto;
use Stsbl\IServ\MailRedirection\Service\RecipientCardResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(RecipientCardResolver::class)]
final class RecipientCardResolverTest extends TestCase
{
    public function testResolvesAndSortsAdministratorRecipientCards(): void
    {
        $users = $this->createMock(IdmUserFetcher::class);
        $groups = $this->createMock(IdmGroupFetcher::class);
        $avatars = $this->createMock(AvatarRendererInterface::class);
        $authorization = $this->createMock(AuthorizationCheckerInterface::class);

        $user = new RecipientUserDto('0fd3d1ec-5146-47ef-8c11-70f23850dd8e', 'zoe.admin', 'Zoe', 'Admin', null, null);
        $group = new RecipientGroupDto('10d3d1ec-5146-47ef-8c11-70f23850dd8e', 'all-staff', 'All staff', null);
        $users->expects($this->once())->method('getFilteredUsers')->willReturn(['zoe' => $user]);
        $groups->expects($this->once())->method('getFilteredGroups')->willReturn(['staff' => $group]);
        $authorization->expects($this->exactly(2))->method('isGranted')->with('ROLE_f66aee04-c335-4299-9cfe-ca7176cc0213')->willReturn(true);
        $avatars->expects($this->once())->method('render')->willReturn('<img alt="Zoe">');
        $avatars->expects($this->once())->method('renderPlaceholder')->willReturn('<span>AS</span>');

        $cards = (new RecipientCardResolver($users, $groups, $avatars, $authorization))->resolve([
            new AutocompleteTagsData('user:zoe.admin', 'zoe.admin', 'user'),
            new AutocompleteTagsData('group:all-staff', 'all-staff', 'group'),
        ]);

        self::assertSame(['All staff', 'Zoe Admin'], array_column($cards, 'name'));
        self::assertSame('/iserv/admin/group/show/all-staff', $cards[0]->link);
        self::assertSame('/iserv/admin/user/show/zoe.admin', $cards[1]->link);
        self::assertSame('<img alt="Zoe">', $cards[1]->avatarHtml);
    }

    public function testUsesProfileLinkForNonAdministratorAndIgnoresUnknownRecipients(): void
    {
        $users = $this->createMock(IdmUserFetcher::class);
        $groups = $this->createMock(IdmGroupFetcher::class);
        $avatars = $this->createMock(AvatarRendererInterface::class);
        $authorization = $this->createMock(AuthorizationCheckerInterface::class);

        $users->method('getFilteredUsers')->willReturn([
            new RecipientUserDto('0fd3d1ec-5146-47ef-8c11-70f23850dd8e', 'zoe.admin', '', '', null, null),
        ]);
        $groups->method('getFilteredGroups')->willReturn([]);
        $authorization->method('isGranted')->willReturn(false);
        $avatars->method('render')->willReturn('<img alt="zoe.admin">');

        $cards = (new RecipientCardResolver($users, $groups, $avatars, $authorization))->resolve([
            new AutocompleteTagsData('user:zoe.admin', 'zoe.admin', 'user'),
            new AutocompleteTagsData('group:missing', 'missing', 'group'),
        ]);

        self::assertCount(1, $cards);
        self::assertSame('zoe.admin', $cards[0]->name);
        self::assertSame('/iserv/account/profile/0fd3d1ec-5146-47ef-8c11-70f23850dd8e', $cards[0]->link);
    }
}
