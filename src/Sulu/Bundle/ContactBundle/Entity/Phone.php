<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * Phone.
 */
class Phone
{
    /**
     * @var string
     */
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    private $phone;

    /**
     * @var int
     */
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    private $id;

    /**
     * @var PhoneType
     */
    #[Groups(['fullAccount', 'fullContact'])]
    private $phoneType;

    /**
     * @var Collection<int, ContactInterface>
     */
    #[Exclude]
    private $contacts;

    /**
     * @var Collection<int, AccountInterface>
     */
    #[Exclude]
    private $accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->accounts = new ArrayCollection();
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return Phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set phoneType.
     *
     * @return Phone
     */
    public function setPhoneType(PhoneType $phoneType)
    {
        $this->phoneType = $phoneType;

        return $this;
    }

    /**
     * Get phoneType.
     *
     * @return PhoneType
     */
    public function getPhoneType()
    {
        return $this->phoneType;
    }

    /**
     * Add contacts.
     *
     * @return Phone
     */
    public function addContact(ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    /**
     * Remove contacts.
     */
    public function removeContact(ContactInterface $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * Get contacts.
     *
     * @return Collection<int, ContactInterface>
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add accounts.
     *
     * @return Phone
     */
    public function addAccount(AccountInterface $account)
    {
        $this->accounts[] = $account;

        return $this;
    }

    /**
     * Remove accounts.
     */
    public function removeAccount(AccountInterface $account)
    {
        $this->accounts->removeElement($account);
    }

    /**
     * Get accounts.
     *
     * @return Collection<int, AccountInterface>
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
