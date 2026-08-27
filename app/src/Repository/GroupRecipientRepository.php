<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Repository;

use Doctrine\Persistence\ManagerRegistry;
use IServ\CrudBundle\Doctrine\ORM\ServiceEntitySpecificationRepository;
use Stsbl\IServ\MailRedirection\Entity\GroupRecipient;

/** @extends ServiceEntitySpecificationRepository<GroupRecipient> */
class GroupRecipientRepository extends ServiceEntitySpecificationRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupRecipient::class);
    }

    /** @return list<GroupRecipient> */
    public function all(): array
    {
        return $this->findAll();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
