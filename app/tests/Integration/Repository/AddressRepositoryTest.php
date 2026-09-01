<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Repository\AddressRepository;
use Stsbl\IServ\MailRedirection\Tests\Common\DatabaseSchemaTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AddressRepository::class)]
final class AddressRepositoryTest extends KernelTestCase
{
    use DatabaseSchemaTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::recreateSchema();
    }

    public function testFindsOnlyEnabledAliasesMatchingRecipientInAlphabeticalOrder(): void
    {
        $this->persistAddress('zebra', true);
        $this->persistAddress('Alpha', true);
        $this->persistAddress('alphabet', false);
        $this->entityManager->flush();

        /** @var AddressRepository $repository */
        $repository = $this->entityManager->getRepository(Address::class);

        self::assertSame(['Alpha'], array_map(static fn(Address $address): ?string => $address->getRecipient(), $repository->findEnabledByRecipientQuery('alp')));
        self::assertSame('zebra', $repository->findOneByRecipient('zebra')?->getRecipient());
    }

    private function persistAddress(string $recipient, bool $enabled): void
    {
        $address = (new Address())
            ->setRecipient($recipient)
            ->setEnabled($enabled)
            ->setComment('test');
        $this->entityManager->persist($address);
    }
}
