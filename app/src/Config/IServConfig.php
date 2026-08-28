<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Config;

use IServ\Library\Config\Config;

/**
 * Module-local access to the IServ system configuration.
 */
final readonly class IServConfig
{
    public function __construct(private Config $config)
    {
    }

    public function domain(): string
    {
        return (string) $this->config->get('Domain');
    }
}
