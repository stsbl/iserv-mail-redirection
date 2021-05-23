<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Stsbl\MailAliasBundle\Model\Import;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
class Importer
{
    public const COLUMN_NUMBER = 4;

    public const COLUMN_NUMBER_WITHOUT_NOTES = 3;

    public const COLUMN_NUMBER_WITHOUT_GROUPS_NOTES = 2;

    /**
     * @var UploadedFile
     */
    private $csvFile;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var \SplFileObject
     */
    private $fileObject;

    /**
     * @var Address[]
     */
    private $newAddresses = [];

    /**
     * @var string[]
     */
    private $warnings = [];

    /**
     * @var bool
     */
    private $enableNewAliases = true;

    /**
     * @var array
     */
    private $lines = [];

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    /**
     * Set uploaded csv file for import
     */
    private function setUploadedFile(UploadedFile $csvFile): void
    {
        if ($csvFile->getMimeType() !== 'text/plain') {
            throw ImportException::invalidMimeType();
        }

        $this->csvFile = $csvFile;
    }

    /**
     * Set if new aliases should enabled or not
     */
    private function setEnableNewAliases(bool $enable): void
    {
        $this->enableNewAliases = $enable;
    }

    /**
     * Transforms the csv file into entities
     */
    public function transform(Import $import): void
    {
        // reset everything
        $this->newAddresses = [];
        $this->warnings = [];
        $this->lines = [];

        if (null === $import->getFile()) {
            throw ImportException::fileIsNull();
        }

        $this->setUploadedFile($import->getFile());
        $this->setEnableNewAliases($import->isEnable());

        if (!$filePath = $this->csvFile->getRealPath()) {
            throw ImportException::pathNotFound();
        }

        $this->fileObject = new \SplFileObject($filePath);
        $this->validateColumnNumber();
        $this->generateEntities();
    }

    /**
     * Validates the number of columns of the csv file and stores the lines into an array
     */
    private function validateColumnNumber(): void
    {
        $currentLine = 1;
        while ($line = $this->fileObject->fgetcsv()) {
            if ($this->fileObject->eof()) {
                break;
            }

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

            $this->lines[] = $line;

            $currentLine++;
        }

        // reset file pointer
        $this->fileObject->rewind();
    }

    /**
     * Generates entities from the csv lines
     */
    private function generateEntities(): void
    {
        foreach ($this->lines as $line) {
            $originalRecipientAct = array_shift($line);
            $userActString = array_shift($line);
            $groupActString = null;
            $note = null;

            if (empty($originalRecipientAct)) {
                $this->warnings[] = _('A line with an empty original recipient was ignored. The listed users and ' .
                    'groups wasn\'t assigned to this recipient.');
                continue;
            }

            if (count($line) > 0) {
                $groupActString = array_shift($line);
            }

            if (count($line) > 0) {
                $note = array_shift($line);
            }

            $addrRepo = $this->em->getRepository(Address::class);
            $originalRecipient = $addrRepo->findOneBy(['recipient' => $originalRecipientAct]);

            if (null === $originalRecipient) {
                $originalRecipient = new Address();
                $originalRecipient->setRecipient($originalRecipientAct);
                $originalRecipient->setEnabled($this->enableNewAliases);
                if ($note !== null) {
                    $originalRecipient->setComment($note);
                }

                $errors = $this->validator->validate($originalRecipient);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->warnings[] = $error->getMessage();
                    }
                    // skip this entity
                    continue;
                }
                $this->em->persist($originalRecipient);
                $this->newAddresses[] = $originalRecipient;
            } else {
                $this->warnings[] = __('The alias %s does already exists! A note for it which is may defined in the ' .
                    'CSV file was ignored.', $originalRecipient->getRecipient());
            }

            $userActs = explode(',', $userActString);
            $groupActs = explode(',', $groupActString);
            $userRepo = $this->em->getRepository(User::class);
            $groupRepo = $this->em->getRepository(Group::class);

            if (!empty($userActString)) {
                foreach ($userActs as $account) {
                    $user = $userRepo->find($account);

                    if (null === $user) {
                        $this->warnings[] = __('A user with the account %s was not found.', $account);
                        continue;
                    }

                    if ($originalRecipient->hasUser($user)) {
                        $this->warnings[] = __(
                            'The user %s is already assigned to the original recipient %s.',
                            $user,
                            $originalRecipient
                        );
                        continue;
                    }

                    $originalRecipient->addUser($user);

                    $this->em->persist($originalRecipient);
                }
            }

            if (!empty($groupActString)) {
                foreach ($groupActs as $g) {
                    $group = $groupRepo->find($g);

                    if (null === $group) {
                        $this->warnings[] = __('A group with the account %s was not found.', $g);
                        continue;
                    }

                    if ($originalRecipient->hasGroup($group)) {
                        $this->warnings[] = __(
                            'The group %s is already assigned to the original recipient %s.',
                            $group,
                            $originalRecipient
                        );
                        continue;
                    }

                    $originalRecipient->addGroup($group);

                    $this->em->persist($originalRecipient);
                }
            }

            $this->em->flush();
        }
    }

    /**
     * Get warnings thrown during import
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get new Address entities generated during import
     *
     * @return Address[]
     */
    public function getNewAddresses(): array
    {
        return $this->newAddresses;
    }
}
