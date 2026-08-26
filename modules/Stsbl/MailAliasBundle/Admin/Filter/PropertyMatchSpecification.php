<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use IServ\CrudBundle\Doctrine\Specification\AbstractSpecification;
use Stsbl\MailAliasBundle\Entity\Address;

final class PropertyMatchSpecification extends AbstractSpecification
{
    public function __construct(private readonly string $property, private readonly mixed $value)
    {
    }

    public function match(QueryBuilder $qb, $dqlAlias)
    {
        return $qb->expr()->eq($dqlAlias . '.' . $this->property, $qb->expr()->literal($this->value));
    }

    public function supports($className): bool
    {
        return $className === Address::class;
    }
}
