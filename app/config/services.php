<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IServ\Library\Config\Config;

// This file is the entry point to configure your own services.
// Files in the packages/ subdirectory configure your dependencies.

return static function (ContainerConfigurator $configurator): void {
    // default configuration for services in *this* file
    $services = $configurator->services()
        ->defaults()
        ->autowire()      // Automatically injects dependencies in your services.
        ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
    ;

    $configurator
        ->parameters()
        ->set('env(POSTGRES_VERSION)', '11.10') // We set a fallback value which normally will be set from the outside
    ;

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('Stsbl\\IServ\\MailRedirection\\', '../src/*')
        ->exclude(['../src/{DependencyInjection,Entity,Tests}/', '../src/Kernel.php'])
    ;

    $configurator->import('services/*.php');

    if ($configurator->env() === 'test') {
        $services
            ->set(Config::class)
            ->args([[]])
        ;
    }
};
