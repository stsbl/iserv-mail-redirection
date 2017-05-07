<?php
// src/Stsbl/MailAliasBundle/Service/ImportService.php
namespace Stsbl\MailAliasBundle\Service;

use Doctrine\ORM\EntityManager;
use Stsbl\MailAliasBundle\Exception\ImportException;
use Stsbl\MailAliasBundle\Entity\Address;
use Stsbl\MailAliasBundle\Entity\UserRecipient;
use Stsbl\MailAliasBundle\Entity\GroupRecipient;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
    const COLUMN_NUMBER = 4;
    
    const COLUMN_NUMBER_WITHOUT_NOTES = 3;
    
    const COLUMN_NUMBER_WITHOUT_GROUPS_NOTES = 2;
    
    /**
     * @var UploadedFile
     */
    private $csvFile;
    
    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var \SplFileObject
     */
    private $fileObject;
    
    /**
     * @var array<Address>
     */
    private $newAddresses = [];
    
    /**
     * @var array<UserRecipient>
     */
    private $newUserRecipients = [];
    
    /**
     * @var array<GroupRecipient>
     */
    private $newGroupRecipients = [];
    
    /**
     * @var array<string>
     */
    private $warnings = [];
    
    /**
     * @var boolean
     */
    private $enableNewAliases = true;
    
    /**
     * @var array
     */
    private $lines = [];
    
    /**
     * The constructor
     * 
     * @var EntityManager
     */
    public function __construct(EntityManager $em = null) 
    {
        if (is_null($em)) {
            throw new \InvalidArgumentException('No instance of EntityManager passed.');
        }
        
        $this->em = $em;
    }
    
    /**
     * Set uploaded csv file for import
     * 
     * @param UploadedFile $csvFile
     */
    public function setUploadedFile(UploadedFile $csvFile)
    {
        if ($csvFile->getMimeType() !== 'text/plain') {
            throw ImportException::invalidMimeType();
        }
        
        $this->csvFile = $csvFile;
    }
    
    /**
     * Set if new aliases should enabled or not
     * 
     * @param boolean $enable
     */
    public function setEnableNewAliases($enable)
    {
        $this->enableNewAliases = $enable;
    }
    
    /**
     * Transforms the csv file into entities
     */
    public function transform()
    {
        // reset everything
        $this->newAddresses = [];
        $this->newUserRecipients = [];
        $this->newGroupRecipients = [];
        $this->warnings = [];
        $this->lines = [];
        
        if (is_null($this->csvFile)) {
            throw ImportException::fileIsNull();
        }
        
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
    private function validateColumnNumber()
    {
        $currentLine = 1;
        $next = 0;
        while ($line = $this->fileObject->fgetcsv()) {
            // if first cells is null and nothing else is set we either have an invalid CSV file or we reached the file end.
            if ($line[0] == null && !isset($line[1]) && !isset($line[self::COLUMN_NUMBER_WITHOUT_GROUPS_NOTES])) {
                break;
            }
            
            // check if column is four (original recipient, users, groups, note) or three (without note) or two (alias and user without a group and a note)
            if (count($line) > self::COLUMN_NUMBER || count($line) < self::COLUMN_NUMBER_WITHOUT_GROUPS_NOTES) {
                throw ImportException::invalidColumnAmount($currentLine, count($line), self::COLUMN_NUMBER_WITHOUT_GROUPS_NOTES);
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
    private function generateEntities()
    {
        foreach ($this->lines as $line) {
            $originalRecipientAct = array_shift($line);
            $userActString = array_shift($line);
            $groupActString = null;
            $note = null;
            
            if (empty($originalRecipientAct)) {
                $this->warnings[] = _('A line with an empty original recipient was ignored. The listed users and groups wasn\'t assigned to this recipient.');
                continue;
            }
           
            if (count($line) > 0) {
                $groupActString = array_shift($line);
            }
            
            if (count($line) > 0) {
                $note = array_shift($line);
            }

            $addrRepo = $this->em->getRepository('StsblMailAliasBundle:Address');
            $originalRecipient = $addrRepo->findOneBy(['recipient' => $originalRecipientAct]);
            
            if ($originalRecipient == null) {
                $originalRecipient = new Address();
                $originalRecipient->setRecipient($originalRecipientAct);
                $originalRecipient->setEnabled($this->enableNewAliases);
                if ($note !== null) $originalRecipient->setComment($note);
         
                $this->em->persist($originalRecipient);
                $this->em->flush();
                $this->newAddresses[] = $originalRecipient;
                
            } else {
                $this->warnings[] = __('The alias %s does already exists! A note for it which is may defined in the CSV file was ignored.', $originalRecipient->getRecipient());
            }
            
            $userActs = explode(',', $userActString);
            $groupActs = explode(',', $groupActString);
            $userRepo = $this->em->getRepository('IServCoreBundle:User');
            $groupRepo = $this->em->getRepository('IServCoreBundle:Group');
            $groupRecipientRepo = $this->em->getRepository('StsblMailAliasBundle:GroupRecipient');
            $userRecipientRepo = $this->em->getRepository('StsblMailAliasBundle:UserRecipient');
           
            if (!empty($userActString)) {
                foreach ($userActs as $u) {
                    $user = $userRepo->find($u);
                
                    if (null === $user) {
                        $this->warnings[] = __('A user with the account %s was not found.', $u);
                        continue;
                    }
                
                    if (null !== $userRecipientRepo->findOneBy(['recipient' => $user, 'originalRecipient' => $originalRecipient])) {
                        $this->warnings[] = __('The user %s is already assigned to the original recipient %s.', (string)$user, (string)$originalRecipient);
                        continue;
                    }
                
                    $userRecipient = new UserRecipient();
                    $userRecipient->setOriginalRecipient($originalRecipient);
                    $userRecipient->setRecipient($user);
                
                    $this->em->persist($userRecipient);
                    $this->em->flush();
                    $this->newUserRecipients[] = $userRecipient;
                }
            }
            
            if (!empty($groupActString)) {
                foreach ($groupActs as $g) {
                    $group = $groupRepo->find($g);
                
                    if (null === $group) {
                        $this->warnings[] = __('A group with the account %s was not found.', $g);
                        continue;
                    }
                
                    if (null !== $groupRecipientRepo->findOneBy(['recipient' => $group, 'originalRecipient' => $originalRecipient])) {
                        $this->warnings[] = __('The group %s is already assigned to the original recipient %s.', (string)$group, (string)$originalRecipient);
                        continue;
                    }
                
                    $groupRecipient = new GroupRecipient();
                    $groupRecipient->setOriginalRecipient($originalRecipient);
                    $groupRecipient->setRecipient($group);
                
                    $this->em->persist($groupRecipient);
                    $this->em->flush();
                    $this->newGroupRecipients[] = $groupRecipient;
                }
            }
        }
    }
    
    /**
     * Get warnings thrown during import
     * 
     * @return array<string>
     */
    public function getWarnings()
    {
        return $this->warnings;
    }
    
    /**
     * Get new <tt>Address</tt> entities, generated during import
     * 
     * @return array<Address>
     */
    public function getNewAddresses()
    {
        return $this->newAddresses;
    }
    
    /**
     * Get new <tt>UserRecipient</tt> entities, generated during import
     * 
     * @return array<UserRecipient>
     */
    public function getNewUserRecipients()
    {
        return $this->newUserRecipients;
    }
    
    /**
     * Get new <tt>GroupRecipient</tt> entities, generated during import
     * 
     * @return array<GroupRecipient>
     */
    public function getNewGroupRecipients()
    {
        return $this->newGroupRecipients;
    }
}
