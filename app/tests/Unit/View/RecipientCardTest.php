<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\View;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\View\RecipientCard;

#[CoversClass(RecipientCard::class)]
final class RecipientCardTest extends TestCase
{
    public function testExposesRecipientPresentationData(): void
    {
        $card = new RecipientCard('Alice', 'alice', '<img>', '/iserv/account/profile/uuid');
        self::assertSame('Alice', $card->name);
        self::assertSame('alice', $card->account);
        self::assertSame('<img>', $card->avatarHtml);
        self::assertSame('/iserv/account/profile/uuid', $card->link);
    }
}
