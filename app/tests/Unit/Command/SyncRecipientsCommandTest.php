<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Unit\Command;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\MailRedirection\Command\SyncRecipientsCommand;
use Stsbl\IServ\MailRedirection\Repository\GroupRecipientRepository;
use Stsbl\IServ\MailRedirection\Repository\UserRecipientRepository;
use Stsbl\IServ\MailRedirection\Service\RecipientIdmSynchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(SyncRecipientsCommand::class)]
final class SyncRecipientsCommandTest extends TestCase
{
    public function testReportsTheSynchronizationResult(): void
    {
        $users = $this->createMock(UserRecipientRepository::class);
        $users->method('all')->willReturn([]);
        $users->expects(self::once())->method('flush');
        $groups = $this->createMock(GroupRecipientRepository::class);
        $groups->method('all')->willReturn([]);
        $groups->expects(self::once())->method('flush');

        $command = new SyncRecipientsCommand(new RecipientIdmSynchronizer(
            $users,
            $groups,
            $this->createMock(IdmUserFetcher::class),
            $this->createMock(IdmGroupFetcher::class),
        ));
        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame("Updated 0 user recipients and 0 group recipients.\n", $tester->getDisplay());
    }
}
