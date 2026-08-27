<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Stsbl\IServ\MailRedirection\Service\CsvFileReaderInterface;
use Stsbl\IServ\MailRedirection\Service\RemoteCsvFileReader;

return static function (ContainerConfigurator $configurator): void {
    $configurator->services()
        ->alias(CsvFileReaderInterface::class, RemoteCsvFileReader::class)
    ;
};
