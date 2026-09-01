<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use IServ\Library\Config\Config;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Controller\MailAliasAutocompleteController;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Tests\Common\DatabaseSchemaTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(MailAliasAutocompleteController::class)]
final class MailAliasAutocompleteControllerTest extends WebTestCase
{
    use DatabaseSchemaTrait;

    private EntityManagerInterface $entityManager;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createClient();
        $this->entityManager = self::recreateSchema();
        self::getContainer()->set(Config::class, new Config(['Domain' => 'example.test']));
        self::getContainer()->set(IdmClientInterface::class, $this->createMock(IdmClientInterface::class));
        self::getContainer()->set(AvatarRendererInterface::class, $this->createMock(AvatarRendererInterface::class));
    }

    public function testReturnsPublicMailAliasSuggestionsForAutocompleteModule(): void
    {
        $alias = (new Address())
            ->setRecipient('help')
            ->setDisplayName('Help group')
            ->setComment('test');
        $this->entityManager->persist($alias);
        $this->entityManager->flush();

        $this->client->request('GET', '/mailalias/autocomplete/api?values=hel');

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Help group <help@example.test>', $response['data'][0]['label']);
        self::assertSame('mailalias:help@example.test', $response['data'][0]['value']);
        self::assertStringEndsWith('.svg', $response['data'][0]['avatar']);
    }

    public function testReturnsAnEmptyArrayForAnEmptySearch(): void
    {
        $this->client->request('GET', '/mailalias/autocomplete/api?query=%20');

        self::assertResponseIsSuccessful();
        self::assertSame('[]', $this->client->getResponse()->getContent());
    }

    public function testUsesTheMailAddressAsLabelWithoutDisplayName(): void
    {
        $this->entityManager->persist((new Address())->setRecipient('support')->setComment(''));
        $this->entityManager->flush();

        $this->client->request('GET', '/mailalias/autocomplete/api?query=support');

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('support@example.test', $response[0]['label']);
        self::assertSame('Mail alias', $response[0]['extra']);
    }
}
