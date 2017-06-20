<?php
// src/Stsbl/MailAlias/Bundle/Enity/Recipient.php
namespace Stsbl\MailAliasBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
use Stsbl\MailAliasBundle\Validator\Constraints as StsblAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

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
 * StsblMailAliasBundle:Recipient
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenes/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mailredirection_addresses")
 * //@ORM\HasLifecycleCallbacks()
 * @DoctrineAssert\UniqueEntity(fields="recipient", message="There is already an entry for that address.")
 * @StsblAssert\Address()
 */
class Address implements CrudInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @var int
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @StsblAssert\SystemAddress()
     * @StsblAssert\LocalPart()
     * @StsblAssert\NotAccount()
     * @ORM\Column(name="recipient", type="text")
     * 
     * @var string
     */
    private $recipient;
    
    /**
     * @ORM\Column(name="enabled", type="boolean")
     * @Assert\NotBlank()
     */
    private $enabled;

    /**
     * @ORM\Column(name="comment", type="text")
     */
    private $comment;

    /**
     * @ORM\OneToMany(targetEntity="UserRecipient", mappedBy="originalRecipient", cascade={"all"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $userRecipients;

    /**
     * @ORM\OneToMany(targetEntity="GroupRecipient", mappedBy="originalRecipient", cascade={"all"}, orphanRemoval=true)
     *
     * @var ArrayCollection
     */
    private $groupRecipients;
    
    /**
     * The constructor
     */
    public function __construct() 
    {
        $this->groupRecipients = new ArrayCollection();
        $this->userRecipients = new ArrayCollection();
    }
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->recipient;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get recipient
     * 
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }
    
    /**
     * Get enabled
     * 
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get comment
     * 
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
    
    /**
     * Get userRecipients
     * 
     * @return ArrayCollection
     */
    public function getUserRecipients()
    {
        return $this->userRecipients;
    }

    /**
     * Get groupRecipients
     * 
     * @return ArrayCollection
     */
    public function getGroupRecipients()
    {
        return $this->groupRecipients;
    }
    
    /**
     * Set recipient
     * 
     * @param string $recipient
     * @return Address
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        
        return $this;
    }
    
    /**
     * Set enabled
     * 
     * @param bool $enabled
     * @return Address
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        
        return $this;
    }
    
    /**
     * Set comment
     * 
     * @param string $comment
     * @return Address
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        
        return $this;
    }
    
    /**
     * Adds one or more user recipients to this original recipient
     * 
     * @param array|UserRecipient $userRecipient
     */
    public function addUserRecipient($userRecipient)
    {
        if(is_array($userRecipient) || $userRecipient instanceof \Traversable) {
            $this->addUserRecipients($userRecipient);
        } else {
            /* @var $userRecipient UserRecipient */
            $userRecipient->setOriginalRecipient($this);
            $this->userRecipients->add($userRecipient);
        }
    }
    
    /**
     * Adds multiple user recipients to this original recipient
     * 
     * @param array $userRecipients
     */
    public function addUserRecipients($userRecipients)
    {
        foreach ($userRecipients as $userRecipient) {
            /* @var $userRecipient UserRecipient */
            $this->addUserRecipient($userRecipient);
        }
    }
    
    /**
     * Set userRecipients
     * 
     * @param array $userRecipients
     * @return array
     */
    public function setUserRecipients($userRecipients)
    {
        if (!is_array($userRecipients) && !$userRecipients instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf('setUserRecipients: Expected %s, got %s.', \Traversable::class, gettype($userRecipients)));
        }
        
        $oldUserRecipients = $this->getUserRecipients()->toArray();
        $this->removeUserRecipients($oldUserRecipients);
        
        foreach ($userRecipients as $userRecipient) {
            if(!$userRecipient instanceof UserRecipient) {
                throw new \InvalidArgumentException(sprintf('setUserRecipients: Expected only instances of type %s in array elements, got %s.', UserRecipient::class, gettype($userRecipient)));
            }
            $this->addUserRecipient($userRecipient);
        }
        
        return array_udiff($oldUserRecipients, $this->getUserRecipients()->toArray(), 
            function (UserRecipient $userRecipient1, UserRecipient $userRecipient2) {
                $id1 = $userRecipient1->getId();
                $id2 = $userRecipient2->getId();
                if ($id1 < $id2) {
                    return -1;
                } else if ($id1 > $id2) {
                    return 1;
                } else {
                    return 0;
                }
            }
        );
    }

    /**
     * removes one or multiple user recipient for original recipient
     * 
     * @param UserRecipient|array $userRecipient
     */
    public function removeUserRecipient($userRecipient)
    {
        if (is_array($userRecipient) || $userRecipient instanceof \Traversable) {
            $this->removeUserRecipients($userRecipient);
        } else {
            $this->userRecipients->removeElement($userRecipient);
        }
    }

    /**
     * removes multiple user recipients from the original recipient
     * 
     * @param array $userRecipients
     */
    public function removeUserRecipients($userRecipients)
    {
        foreach ($userRecipients as $userRecipient) {
            $this->removeUserRecipient($userRecipient);
        }
    }

    /**
     * Adds one or more group recipients to this original recipient
     * 
     * @param array|GroupRecipient $groupRecipient
     */
    public function addGroupRecipient($groupRecipient)
    {
        if(is_array($groupRecipient) || $groupRecipient instanceof \Traversable) {
            $this->addGroupRecipients($groupRecipient);
        } else {
            /* @var $groupRecipient GroupRecipient */
            $groupRecipient->setOriginalRecipient($this);
            $this->groupRecipients->add($groupRecipient);
        }
    }
    
    /**
     * Adds multiple group recipients to this original recipient
     * 
     * @param array $groupRecipients
     */
    public function addGroupRecipients($groupRecipients)
    {
        foreach ($groupRecipients as $groupRecipient) {
            /* @var $groupRecipient GroupRecipient */
            $this->addGroupRecipient($groupRecipient);
        }
    }
    
    /**
     * Set groupRecipients
     * 
     * @param array $groupRecipients
     * @return array
     */
    public function setGroupRecipients($groupRecipients)
    {
        if (!is_array($groupRecipients) && !$groupRecipients instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf('setGroupRecipients: Expected %s, got %s.', \Traversable::class, gettype($groupRecipients)));
        }
        
        $oldGroupRecipients = $this->getGroupRecipients()->toArray();
        $this->removeGroupRecipients($oldGroupRecipients);
        
        foreach ($groupRecipients as $groupRecipient) {
            if(!$groupRecipient instanceof GroupRecipient) {
                throw new \InvalidArgumentException(sprintf('setGroupRecipients: Expected only instances of type %s in array elements, got %s.', GroupRecipient::class, gettype($groupRecipient)));
            }
            $this->addGroupRecipient($groupRecipient);
        }
        
        return array_udiff($oldGroupRecipients, $this->getGroupRecipients()->toArray(), 
            function (GroupRecipient $groupRecipient1, GroupRecipient $groupRecipient2) {
                $id1 = $groupRecipient1->getId();
                $id2 = $groupRecipient2->getId();
                if ($id1 < $id2) {
                    return -1;
                } else if ($id1 > $id2) {
                    return 1;
                } else {
                    return 0;
                }
            }
        );
    }
    
    /**
     * removes one or multiple group recipient for original recipient
     * 
     * @param GroupRecipient|array $groupRecipient
     */
    public function removeGroupRecipient($groupRecipient)
    {
        if (is_array($groupRecipient) || $groupRecipient instanceof \Traversable) {
            $this->removeGroupRecipients($groupRecipient);
        } else {
            $this->groupRecipients->removeElement($groupRecipient);
        }
    }

    /**
     * removes multiple group recipients from the original recipient
     * 
     * @param array $groupRecipients
     */
    public function removeGroupRecipients($groupRecipients)
    {
        foreach ($groupRecipients as $groupRecipient) {
            $this->removeGroupRecipient($groupRecipient);
        }
    }
}
