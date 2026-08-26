<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Entity;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Entity\Address;

final class AddressRecipientTest extends TestCase
{
    public function testSplitsAutocompleteRecipientsIntoLocalAccountAssociations(): void
    {
        $address = new Address();
        $address->setRecipients([
            new AutocompleteTagsData('user:max', 'max', 'user'),
            new AutocompleteTagsData('group:teachers', 'teachers', 'group'),
        ]);

        self::assertSame(['max'], array_map(static fn ($recipient): string => $recipient->getAccount(), $address->getUsers()->toArray()));
        self::assertSame(['teachers'], array_map(static fn ($recipient): string => $recipient->getAccount(), $address->getGroups()->toArray()));
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
            static fn (AutocompleteTagsData $recipient): string => $recipient->getValue(),
            $address->getRecipients(),
        ));
    }
}
