<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/mailaliases', name: 'admin_mailalias_legacy_redirect', methods: ['GET'])]
final class RedirectController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->redirectToRoute('admin_mailalias_index', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
