<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use IServ\CrudBundle\Doctrine\ORM\ServiceEntitySpecificationRepository;
use Stsbl\MailAliasBundle\Entity\Address;

/**
 * @extends ServiceEntitySpecificationRepository<Address>
 */
class AddressRepository extends ServiceEntitySpecificationRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findOneByRecipient(string $recipient): ?Address
    {
        /** @var Address|null $address */
        $address = $this->findOneBy(['recipient' => $recipient]);

        return $address;
    }

    public function persist(Address $address): void
    {
        $this->getEntityManager()->persist($address);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
