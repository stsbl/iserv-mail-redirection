<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Doctrine\Specification\AbstractSpecification;
use Stsbl\MailAliasBundle\Entity\Address;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class AliasAssociationSpecification extends AbstractSpecification
{
    /**
     * @var bool
     */
    private $filterWithoutGroup;

    /**
     * @var bool
     */
    private $filterWithoutUser;

    public function __construct(bool $filterWithoutGroup, bool $filterWithoutUser)
    {
        $this->filterWithoutGroup = $filterWithoutGroup;
        $this->filterWithoutUser = $filterWithoutUser;
    }

    /**
     * {@inheritDoc}
     */
    public function match(QueryBuilder $qb, $dqlAlias)
    {
        $subUser = clone $qb;
        $subUser->resetDQLPart('from');

        $qb->leftJoin('parent.groups', 'ag');
        $qb->leftJoin('parent.users', 'au');

        $subUser
            ->select('u')
            ->from(User::class, 'u')
            ->where($subUser->expr()->eq('u.username', 'au.username'))
        ;

        if ($this->filterWithoutUser) {
            $userExpression = $qb->expr()->not($qb->expr()->exists($subUser));
        } else {
            $userExpression = $qb->expr()->exists($subUser);
        }

        $subGroup = clone $qb;
        $subGroup->resetDQLPart('from');

        $subGroup
            ->select('g')
            ->from(Group::class, 'g')
            ->where($subUser->expr()->eq('g.account', 'ag.account'))
        ;

        if ($this->filterWithoutGroup) {
            $groupExpression = $qb->expr()->not($qb->expr()->exists($subGroup));
        } else {
            $groupExpression = $qb->expr()->exists($subGroup);
        }

        return $qb->expr()->andX($userExpression, $groupExpression);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($className): bool
    {
        return Address::class === $className;
    }
}
