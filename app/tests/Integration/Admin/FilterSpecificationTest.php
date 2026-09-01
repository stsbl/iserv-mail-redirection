<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Tests\Integration\Admin;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\MailRedirection\Admin\Filter\AliasAssociationSpecification;
use Stsbl\IServ\MailRedirection\Admin\Filter\PropertyMatchSpecification;
use Stsbl\IServ\MailRedirection\Entity\Address;
use Stsbl\IServ\MailRedirection\Tests\Common\DatabaseSchemaTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AliasAssociationSpecification::class)]
#[CoversClass(PropertyMatchSpecification::class)]
final class FilterSpecificationTest extends KernelTestCase
{
    use DatabaseSchemaTrait;

    public function testBuildsAssociationAndPropertyExpressions(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::recreateSchema();
        $queryBuilder = $entityManager->createQueryBuilder()->select('parent')->from(Address::class, 'parent');
        $association = new AliasAssociationSpecification(true, false);
        $property = new PropertyMatchSpecification('enabled', true);

        self::assertTrue($association->supports(Address::class));
        self::assertFalse($association->supports(\stdClass::class));
        self::assertTrue($property->supports(Address::class));
        self::assertFalse($property->supports(\stdClass::class));
        $queryBuilder->where($association->match($queryBuilder, 'parent'));
        $queryBuilder->andWhere($property->match($queryBuilder, 'parent'));

        self::assertStringContainsString('LEFT JOIN parent.groups ag', $queryBuilder->getDQL());
        self::assertStringContainsString('parent.enabled = true', $queryBuilder->getDQL());

        $requiredAssociations = new AliasAssociationSpecification(false, true);
        $requiredQueryBuilder = $entityManager->createQueryBuilder()->select('parent')->from(Address::class, 'parent');
        self::assertStringContainsString('IS NOT NULL', (string) $requiredAssociations->match($requiredQueryBuilder, 'parent'));
    }
}
