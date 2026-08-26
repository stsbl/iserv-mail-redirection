<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Command;

use Stsbl\MailAliasBundle\Service\RecipientIdmSynchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncRecipientsCommand extends Command
{
    protected static $defaultName = 'iserv:mailalias:sync-recipients';

    public function __construct(
        private readonly RecipientIdmSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Synchronizes mail alias recipient account names from IDM.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->synchronizer->sync();
        $output->writeln(sprintf('Updated %d user recipients and %d group recipients.', $result['users'], $result['groups']));

        return Command::SUCCESS;
    }
}
