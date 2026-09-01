<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IServ\Library\Config\Config;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use Stsbl\IServ\MailRedirection\Service\IdmRecipientLookup;

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
            ->public()
        ;

        // Web tests replace remote integrations with deterministic doubles.
        $services->get(IdmRecipientLookup::class)->public();
        $services->set(IdmClientInterface::class)->synthetic()->public();
        $services->set(AvatarRendererInterface::class)->synthetic()->public();
    }
};
