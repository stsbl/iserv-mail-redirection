<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Twig;

use Stsbl\MailAliasBundle\Service\RecipientCardResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RecipientCardExtension extends AbstractExtension
{
    public function __construct(private readonly RecipientCardResolver $resolver) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('mail_alias_recipient_cards', $this->resolver->resolve(...))];
    }
}
