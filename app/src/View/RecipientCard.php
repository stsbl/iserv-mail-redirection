<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\View;

final readonly class RecipientCard
{
    public function __construct(
        public string $name,
        public string $account,
        public string $avatarHtml,
        public ?string $link,
    ) {
    }
}
