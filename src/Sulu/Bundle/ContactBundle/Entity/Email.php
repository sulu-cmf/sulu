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
 * Email.
 */
class Email
{
    /**
     * @var string
     */
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    private $email;

    /**
     * @var int
     */
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    private $id;

    /**
     * @var EmailType
     */
    #[Groups(['fullAccount', 'fullContact'])]
    private $emailType;

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
     * Set email.
     *
     * @param string $email
     *
     * @return Email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * Set emailType.
     *
     * @return Email
     */
    public function setEmailType(EmailType $emailType)
    {
        $this->emailType = $emailType;

        return $this;
    }

    /**
     * Get emailType.
     *
     * @return EmailType
     */
    public function getEmailType()
    {
        return $this->emailType;
    }

    /**
     * Add contacts.
     *
     * @return Email
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
     * @return Email
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
