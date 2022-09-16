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
 * Fax.
 */
class Fax
{
    /**
     * @var string
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $fax;

    /**
     * @var int
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var FaxType
     *
     * @Groups({"fullAccount", "fullContact"})
     */
    private $faxType;

    /**
     * @var Collection<int, ContactInterface>
     *
     * @Exclude
     */
    private $contacts;

    /**
     * @var Collection<int, AccountInterface>
     *
     * @Exclude
     */
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
     * Set fax.
     *
     * @param string $fax
     *
     * @return Fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get fax.
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
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
     * Set faxType.
     *
     * @return Fax
     */
    public function setFaxType(FaxType $faxType)
    {
        $this->faxType = $faxType;

        return $this;
    }

    /**
     * Get faxType.
     *
     * @return FaxType
     */
    public function getFaxType()
    {
        return $this->faxType;
    }

    /**
     * Add contacts.
     *
     * @return Fax
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
     * @return Fax
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
