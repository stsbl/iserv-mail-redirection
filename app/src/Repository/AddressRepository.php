<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Repository;

use Doctrine\Persistence\ManagerRegistry;
use IServ\CrudBundle\Doctrine\ORM\ServiceEntitySpecificationRepository;
use Stsbl\IServ\MailRedirection\Entity\Address;

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

    /** @return list<Address> */
    public function findEnabledByRecipientQuery(string $query): array
    {
        /** @var list<Address> $addresses */
        $addresses = $this->createQueryBuilder('address')
            ->andWhere('address.enabled = :enabled')
            ->andWhere('LOWER(address.recipient) LIKE LOWER(:query)')
            ->setParameter('enabled', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('address.recipient', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $addresses;
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
