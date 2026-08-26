<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Service;

use IServ\Bundle\IdmDataBroker\Contract\IdmGroupFetcher;
use IServ\Bundle\IdmDataBroker\Contract\IdmUserFetcher;
use IServ\FilesystemBundle\FilePicker\Domain\PickedFile;
use IServ\Library\User\User\Username;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Entity\GroupRecipient;
use Stsbl\MailAliasBundle\Entity\UserRecipient;
use Stsbl\MailAliasBundle\Idm\RecipientGroupDto;
use Stsbl\MailAliasBundle\Idm\RecipientUserDto;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Stsbl\MailAliasBundle\Model\Import;
use Stsbl\MailAliasBundle\Model\ImportResult;
use Stsbl\MailAliasBundle\Repository\AddressRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
 * Service for importing mail aliases from a csv file
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT licenses <https://opensource.org/licenses/MIT>
 */
final class Importer
{
    public const COLUMN_NUMBER = 4;

    public const COLUMN_NUMBER_WITHOUT_GROUPS_NOTES = 2;

    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly ValidatorInterface $validator,
        private readonly IdmUserFetcher $idmUserFetcher,
        private readonly IdmGroupFetcher $idmGroupFetcher,
        private readonly CsvFileReaderInterface $fileReader,
    ) {
    }

    /**
     * Transforms the csv file into entities
     */
    public function import(Import $import): ImportResult
    {
        $file = $import->getFile();
        if (null === $file) {
            throw ImportException::fileIsNull();
        }

        $this->validateMimeType($file);
        $stream = $this->fileReader->open($file);

        try {
            $lines = $this->validateColumnNumber($stream);
        } finally {
            fclose($stream);
        }

        return $this->generateEntities($lines, $import->isEnable());
    }

    /**
     * @return list<list<string|null>>
     */
    private function validateColumnNumber($stream): array
    {
        $lines = [];
        $currentLine = 1;
        while (false !== $line = fgetcsv($stream)) {
            // check if column is four (original recipient, users, groups, note) or three (without note)
            // or two (alias and user without a group and a note)
            $lineCount = \count($line);
            if ($lineCount > self::COLUMN_NUMBER || $lineCount < self::COLUMN_NUMBER_WITHOUT_GROUPS_NOTES) {
                throw ImportException::invalidColumnAmount(
                    $currentLine,
                    $lineCount,
                    self::COLUMN_NUMBER_WITHOUT_GROUPS_NOTES
                );
            }

            $lines[] = $line;

            $currentLine++;
        }

        return $lines;
    }

    /**
     * Generates entities from the csv lines
     */
    /** @param list<list<string|null>> $lines */
    private function generateEntities(array $lines, bool $enableNewAliases): ImportResult
    {
        $newAddresses = [];
        $warnings = [];

        foreach ($lines as $line) {
            $originalRecipientAct = array_shift($line);
            $userActString = array_shift($line);
            $groupActString = null;
            $note = null;

            if (empty($originalRecipientAct)) {
                $warnings[] = _('A line with an empty original recipient was ignored. The listed users and ' .
                    'groups wasn\'t assigned to this recipient.');
                continue;
            }

            if (count($line) > 0) {
                $groupActString = array_shift($line);
            }

            if (count($line) > 0) {
                $note = array_shift($line);
            }

            $originalRecipient = $this->addressRepository->findOneByRecipient($originalRecipientAct);

            if (null === $originalRecipient) {
                $originalRecipient = new Address();
                $originalRecipient->setRecipient($originalRecipientAct);
                $originalRecipient->setEnabled($enableNewAliases);
                if ($note !== null) {
                    $originalRecipient->setComment($note);
                }

                $errors = $this->validator->validate($originalRecipient);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $warnings[] = $error->getMessage();
                    }
                    // skip this entity
                    continue;
                }
                $this->addressRepository->persist($originalRecipient);
                $newAddresses[] = $originalRecipient;
            } else {
                $warnings[] = __('The alias %s does already exists! A note for it which is may defined in the ' .
                    'CSV file was ignored.', $originalRecipient->getRecipient());
            }

            $userActs = explode(',', $userActString);
            $groupActs = explode(',', (string) $groupActString);
            if (!empty($userActString)) {
                foreach ($userActs as $account) {
                    $users = $this->idmUserFetcher->getFilteredUsers(['user' => $account, 'deleted' => 'false'], RecipientUserDto::class);
                    $user = current($users);

                    if (!$user instanceof RecipientUserDto || $user->account !== $account) {
                        $warnings[] = __('A user with the account %s was not found.', $account);
                        continue;
                    }

                    $username = new Username($account);
                    if ($originalRecipient->hasUser($username)) {
                        $warnings[] = __(
                            'The user %s is already assigned to the original recipient %s.',
                            $user,
                            $originalRecipient
                        );
                        continue;
                    }

                    $originalRecipient->addUser(new UserRecipient($username, $user->uuid));

                    $this->addressRepository->persist($originalRecipient);
                }
            }

            if (!empty($groupActString)) {
                foreach ($groupActs as $g) {
                    $groups = $this->idmGroupFetcher->getFilteredGroups(['group' => $g, 'deleted' => 'false'], RecipientGroupDto::class);
                    $group = current($groups);

                    if (!$group instanceof RecipientGroupDto || $group->account !== $g) {
                        $warnings[] = __('A group with the account %s was not found.', $g);
                        continue;
                    }

                    if ($originalRecipient->hasGroupAccount($g)) {
                        $warnings[] = __(
                            'The group %s is already assigned to the original recipient %s.',
                            $group,
                            $originalRecipient
                        );
                        continue;
                    }

                    $originalRecipient->addGroup(new GroupRecipient($g, $group->uuid));

                    $this->addressRepository->persist($originalRecipient);
                }
            }

            $this->addressRepository->flush();
        }

        return new ImportResult($newAddresses, $warnings);
    }

    private function validateMimeType(PickedFile $file): void
    {
        $mimetype = $this->fileReader->getMimeType($file);
        if (!in_array($mimetype, ['text/plain', 'text/csv'], true)) {
            throw ImportException::invalidMimeType();
        }
    }
}
