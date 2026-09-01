<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Validator\Constraints;

use Stsbl\IServ\MailRedirection\Entity\Address as AddressEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

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
 * Validator for Address
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class AddressValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Address) {
            throw new UnexpectedTypeException($constraint, Address::class);
        }

        if (!$value instanceof AddressEntity) {
            throw new UnexpectedTypeException($value, AddressEntity::class);
        }

        $groupRecipients = $value->getGroups()->toArray();
        $groupRecipientAccounts = [];
        $groupRecipientEntities = [];

        foreach ($groupRecipients as $groupRecipient) {
            $groupRecipientAccounts[] = (string) $groupRecipient->getAccount();
            $groupRecipientEntities[(string) $groupRecipient->getAccount()] = (string) $groupRecipient->getAccount();
        }

        $duplicatedGroupRecipients = array_unique(array_diff_assoc(
            $groupRecipientAccounts,
            array_unique($groupRecipientAccounts),
        ));

        foreach ($duplicatedGroupRecipients as $duplicate) {
            $this->context->buildViolation(sprintf(
                $constraint->getDuplicateGroupMessage(),
                $groupRecipientEntities[$duplicate],
                $value->getRecipient(),
            ))->atPath('recipient')->addViolation();
        }

        $userRecipients = $value->getUsers()->toArray();
        $userRecipientAccounts = [];
        $userRecipientEntities = [];

        foreach ($userRecipients as $userRecipient) {
            $userRecipientAccounts[] = (string) $userRecipient->getUsername();
            $userRecipientEntities[(string) $userRecipient->getUsername()] = (string) $userRecipient->getUsername();
        }

        $duplicatedUserRecipients = array_unique(array_diff_assoc(
            $userRecipientAccounts,
            array_unique($userRecipientAccounts),
        ));

        foreach ($duplicatedUserRecipients as $duplicate) {
            $this->context->buildViolation(sprintf(
                $constraint->getDuplicateUserMessage(),
                $userRecipientEntities[$duplicate],
                $value->getRecipient(),
            ))->atPath('recipient')->addViolation();
        }
    }

}
