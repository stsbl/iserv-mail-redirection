<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Twig;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Service\RecipientCardResolver;
use Stsbl\IServ\MailRedirection\Twig\RecipientCardExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(RecipientCardExtension::class)]
final class RecipientCardExtensionTest extends TestCase
{
    public function testExposesRecipientCardsTwigFunction(): void
    {
        $resolver = new RecipientCardResolver(
            $this->createMock(IdmUserFetcher::class),
            $this->createMock(IdmGroupFetcher::class),
            $this->createMock(AvatarRendererInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
        );

        $functions = (new RecipientCardExtension($resolver))->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('mail_alias_recipient_cards', $functions[0]->getName());
        self::assertSame([], ($functions[0]->getCallable())([]));
    }
}
