<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Entity;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;

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
}
