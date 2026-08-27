<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Controller;

use IServ\Library\Avatar\AvatarSize;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Avatar\Renderer\AvatarRenderStyle;
use IServ\Library\Avatar\Renderer\Exception\AvatarRendererException;
use IServ\Library\Avatar\UrlGenerator\AvatarPlaceholderStyle;
use IServ\Library\Uuid\Uuid;
use Stsbl\IServ\MailRedirection\Config\IServConfig;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Repository\AddressRepository;
use Stsbl\IServ\MailRedirection\Service\IdmRecipientLookup;
use Stsbl\IServ\MailRedirection\Security\Privilege;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MailAliasAutocompleteController extends AbstractController
{
    #[Route('/mailalias/autocomplete/api', name: 'mailalias_autocomplete_api', methods: ['GET'])]
    public function aliases(Request $request, AddressRepository $addresses, IServConfig $config): JsonResponse
    {
        $query = $request->query->get('query') ?? $request->query->get('values');
        if (!is_string($query) || trim($query) === '') {
            return new JsonResponse([]);
        }

        $suggestions = array_map(static function (Address $address) use ($config): array {
            $mail = $address->getRecipient() . '@' . $config->domain();
            $label = $address->getDisplayName() ?: $mail;

            return [
                'label' => $label,
                'text' => $label,
                // RemoteAutocompleteSource separates its wire value at the
                // first colon. The public prefix marks the address as a
                // globally usable mail recipient for Mail and Calendar.
                'value' => 'public:' . $mail,
                'source' => 'public',
                'avatar' => null,
                'avatarHtml' => null,
                'icon' => 'fa-envelope',
                'extra' => $address->getDisplayName() ? $mail : _('Mail alias'),
                'certainty' => 5,
                'fuzzy' => false,
                'expandable' => false,
                'readonly' => false,
            ];
        }, $addresses->findEnabledByRecipientQuery($query));

        return $request->query->has('values') ? new JsonResponse(['data' => $suggestions]) : new JsonResponse($suggestions);
    }

    #[Route('/admin/mailalias/recipients', name: 'admin_mailalias_recipients', methods: ['GET'])]
    #[IsGranted(Privilege::ADMIN)]
    public function recipients(
        Request $request,
        IdmRecipientLookup $lookup,
        AvatarRendererInterface $avatarRenderer,
    ): JsonResponse {
        $type = $request->query->getString('type');
        $types = explode(',', $type);
        if ($type === '' || array_diff($types, ['group', 'user'])) {
            throw $this->createNotFoundException();
        }

        $query = $request->query->getString('query');
        $suggestions = [];
        if (in_array('group', $types, true)) {
            foreach (['exact' => 10, 'partial' => 5, 'fuzzy' => 1] as $bucket => $certainty) {
                foreach ($lookup->groups($query)[$bucket] ?? [] as $group) {
                    $account = $group['group'] ?? null;
                    if (!is_string($account) || $account === '') {
                        continue;
                    }
                    $label = (string) ($group['name'] ?? $account);
                    $suggestions[] = $this->suggestion('group', $account, $label, _('Group'), $certainty, $bucket === 'fuzzy', $this->renderGroupAvatar($avatarRenderer, $label));
                }
            }
        }
        if (in_array('user', $types, true)) {
            foreach (['exact' => 10, 'partial' => 5, 'fuzzy' => 1] as $bucket => $certainty) {
                foreach ($lookup->users($query)[$bucket] ?? [] as $user) {
                    $account = $user['user'] ?? null;
                    if (!is_string($account) || $account === '') {
                        continue;
                    }
                    $label = trim(sprintf('%s %s', $user['firstname'] ?? '', $user['lastname'] ?? '')) ?: $account;
                    $suggestions[] = $this->suggestion('user', $account, $label, (string) ($user['auxInfo'] ?? _('User')), $certainty, $bucket === 'fuzzy', $this->renderUserAvatar($avatarRenderer, $user['hexUuid'] ?? null));
                }
            }
        }

        return new JsonResponse($suggestions);
    }

    /** @return array<string, mixed> */
    private function suggestion(string $source, string $account, string $label, string $extra, int $certainty, bool $fuzzy, ?string $avatarHtml): array
    {
        return ['label' => $label, 'text' => $label, 'value' => $source . ':' . $account, 'source' => $source, 'avatarHtml' => $avatarHtml, 'icon' => $source === 'group' ? 'fa-users' : 'fa-user', 'extra' => $extra, 'certainty' => $certainty, 'fuzzy' => $fuzzy, 'expandable' => false, 'readonly' => false];
    }

    private function renderUserAvatar(AvatarRendererInterface $renderer, mixed $uuid): ?string
    {
        if (!is_string($uuid) || $uuid === '') {
            return null;
        }
        try {
            return $renderer->render(Uuid::createFromNormalized($uuid), AvatarSize::default());
        } catch (\InvalidArgumentException|AvatarRendererException) {
            return null;
        }
    }

    private function renderGroupAvatar(AvatarRendererInterface $renderer, string $name): ?string
    {
        try {
            return $renderer->renderPlaceholder($name, AvatarSize::default(), AvatarRenderStyle::ROUNDED, AvatarPlaceholderStyle::GROUP);
        } catch (AvatarRendererException) {
            return null;
        }
    }
}
