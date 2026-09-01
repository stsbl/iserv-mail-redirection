<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Domain\GroupAccount;
use Stsbl\IServ\MailRedirection\Domain\Username;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;
use Stsbl\IServ\MailRedirection\Entity\UserRecipient;
use Stsbl\IServ\MailRedirection\Repository\GroupRecipientRepository;
use Stsbl\IServ\MailRedirection\Repository\UserRecipientRepository;
use Stsbl\IServ\MailRedirection\Tests\Common\DatabaseSchemaTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(UserRecipientRepository::class)]
#[CoversClass(GroupRecipientRepository::class)]
final class RecipientRepositoryTest extends KernelTestCase
{
    use DatabaseSchemaTrait;

    public function testListsAndFlushesRecipientRepositories(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::recreateSchema();
        $address = (new Address())->setRecipient('help')->setComment('test');
        $address->addUser(new UserRecipient(new Username('alice')));
        $address->addGroup(new GroupRecipient(new GroupAccount('teachers')));
        $entityManager->persist($address);
        $entityManager->flush();

        /** @var UserRecipientRepository $users */
        $users = $entityManager->getRepository(UserRecipient::class);
        /** @var GroupRecipientRepository $groups */
        $groups = $entityManager->getRepository(GroupRecipient::class);
        self::assertCount(1, $users->all());
        self::assertCount(1, $groups->all());
        $users->flush();
        $groups->flush();
    }
}
