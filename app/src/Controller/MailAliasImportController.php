<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Controller;

use IServ\Bundle\AdminLog\Logger\AdminLoggerInterface;
use IServ\Bundle\Flash\Flash\FlashInterface;
use IServ\Bundle\Flash\Flash\FlashMessage;
use Stsbl\IServ\MailRedirection\Admin\AddressAdmin;
use Stsbl\IServ\MailRedirection\Config\IServConfig;
use Stsbl\IServ\MailRedirection\Exception\ImportException;
use Stsbl\IServ\MailRedirection\Form\Type\ImportType;
use Stsbl\IServ\MailRedirection\Model\Import;
use Stsbl\IServ\MailRedirection\Service\Importer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MailAliasImportController extends AbstractController
{
    #[Route('/admin/mailalias/import', name: 'admin_mailalias_import', methods: ['GET', 'POST'])]
    #[IsGranted('PRIV_f5f8da73-2a25-44ec-af7b-3acc8aa7fce4')]
    public function __invoke(Importer $importer, AdminLoggerInterface $logger, Request $request, IServConfig $config, FlashInterface $flash): Response
    {
        $form = $this->createForm(ImportType::class);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('mail_alias/import.html.twig', ['importForm' => $form->createView(), 'importExplanation' => AddressAdmin::getImportExplanation(), 'importExplanationFieldList' => AddressAdmin::getImportExplanationFieldList()]);
        }

        try {
            /** @var Import $import */
            $import = $form->getData();
            $result = $importer->import($import);
            $domain = $config->domain();
            foreach ($result->getNewAddresses() as $address) {
                $logger->writeForModule(sprintf(AddressAdmin::LOG_ALIAS_ADDED, $address, $domain), 'Mail aliases');
            }
            foreach ($result->getWarnings() as $warning) {
                $flash->add(new FlashMessage('warning', $warning));
            }
            $flash->add(new FlashMessage('success', __('Imported %d mail aliases.', count($result->getNewAddresses()))));
        } catch (ImportException $exception) {
            $message = $exception->getMessage();
            if ($message === ImportException::MESSAGE_INVALID_COLUMN_AMOUNT && $exception->getFileLine() !== null) {
                $message = __('%s near line %s.', rtrim($message, '.'), $exception->getFileLine());
            }
            $flash->add(new FlashMessage('error', _($message)));
        }

        return new RedirectResponse($this->generateUrl('admin_mailalias_index'));
    }
}
