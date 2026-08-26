<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Model;

use Stsbl\MailAliasBundle\Entity\Address;

/**
 * Immutable result of one CSV import.
 */
final class ImportResult
{
    /**
     * @param Address[] $newAddresses
     * @param string[] $warnings
     */
    public function __construct(
        private readonly array $newAddresses,
        private readonly array $warnings,
    ) {
    }

    /** @return Address[] */
    public function getNewAddresses(): array
    {
        return $this->newAddresses;
    }

    /** @return string[] */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
