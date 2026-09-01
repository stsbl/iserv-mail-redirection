<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Entity;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;
use Stsbl\IServ\MailRedirection\Domain\Username;

#[CoversClass(Address::class)]
final class AddressRecipientTest extends TestCase
{
    public function testSplitsAutocompleteRecipientsIntoLocalAccountAssociations(): void
    {
        $address = new Address();
        $address->setRecipients([
            new AutocompleteTagsData('user:max', 'max', 'user'),
            new AutocompleteTagsData('group:teachers', 'teachers', 'group'),
        ]);

        self::assertSame(['max'], array_map(static fn($recipient): string => (string) $recipient->getUsername(), $address->getUsers()->toArray()));
        self::assertSame(['teachers'], array_map(static fn($recipient): string => $recipient->getAccount()->export(), $address->getGroups()->toArray()));
    }

    public function testDeduplicatesAndProjectsRecipientTags(): void
    {
        $address = new Address();
        $address->setRecipients([
            new AutocompleteTagsData('user:max', 'max', 'user'),
            new AutocompleteTagsData('user:max', 'max', 'user'),
            new AutocompleteTagsData('group:teachers', 'teachers', 'group'),
        ]);

        self::assertSame(['user:max', 'group:teachers'], array_map(
            static fn(AutocompleteTagsData $recipient): string => $recipient->getValue(),
            $address->getRecipients(),
        ));
    }

    public function testUsesGroupAccountDomainType(): void
    {
        $recipient = new GroupRecipient(new GroupAccount('teachers'));

        self::assertInstanceOf(GroupAccount::class, $recipient->getAccount());
        self::assertSame('teachers', $recipient->getAccount()->export());
    }
    public function testReplacesAndRemovesRecipientAssociations(): void
    {
        $address = (new Address())->setRecipient('help')->setEnabled(false)->setComment('note')->setDisplayName('Help');
        $user = new UserRecipient(new Username('alice'));
        $group = new GroupRecipient(new GroupAccount('teachers'));
        $address->addUser($user)->addGroup($group);

        self::assertSame('help', $address->getRecipient());
        self::assertFalse($address->getEnabled());
        self::assertSame('note', $address->getComment());
        self::assertSame('Help', $address->getDisplayName());
        self::assertTrue($address->hasUser(new Username('alice')));
        self::assertTrue($address->hasGroupAccount(new GroupAccount('teachers')));

        $address->removeUser($user)->removeGroup($group);
        self::assertCount(0, $address->getUsers());
        self::assertCount(0, $address->getGroups());
    }
}
