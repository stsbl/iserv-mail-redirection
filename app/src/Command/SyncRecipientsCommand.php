<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Command;

use Stsbl\IServ\MailRedirection\Service\RecipientIdmSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'iserv:mailalias:sync-recipients', description: 'Synchronizes mail alias recipient account names from IDM.')]
final class SyncRecipientsCommand extends Command
{
    public function __construct(
        private readonly RecipientIdmSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->synchronizer->sync();
        $output->writeln(sprintf('Updated %d user recipients and %d group recipients.', $result['users'], $result['groups']));

        return Command::SUCCESS;
    }
}
