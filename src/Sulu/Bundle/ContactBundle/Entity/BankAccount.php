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

/**
 * BankAccount.
 */
class BankAccount
{
    /**
     * @var string|null
     */
    private $bankName;

    /**
     * @var string|null
     */
    private $bic;

    /**
     * @var string
     */
    private $iban;

    /**
     * @var bool
     */
    private $public = false;

    /**
     * @var int
     */
    private $id;

    /**
     * @var Collection<int, AccountInterface>
     */
    #[Exclude]
    private $accounts;

    /**
     * @var Collection<int, ContactInterface>
     */
    private $contacts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    /**
     * Set bic.
     *
     * @param string|null $bic
     *
     * @return BankAccount
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic.
     *
     * @return string|null
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set iban.
     *
     * @param string $iban
     *
     * @return BankAccount
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban.
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return BankAccount
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public.
     *
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
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
     * Add accounts.
     *
     * @return BankAccount
     */
    public function addAccount(AccountInterface $accounts)
    {
        $this->accounts[] = $accounts;

        return $this;
    }

    /**
     * Remove accounts.
     */
    public function removeAccount(AccountInterface $accounts)
    {
        $this->accounts->removeElement($accounts);
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

    /**
     * Set bankName.
     *
     * @param string|null $bankName
     *
     * @return BankAccount
     */
    public function setBankName($bankName)
    {
        $this->bankName = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string|null
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * Add contacts.
     *
     * @return BankAccount
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
}
