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
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Social media profile belonging to account or contact.
 */
#[ExclusionPolicy('All')]
class SocialMediaProfile
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var SocialMediaProfileType
     */
    private $socialMediaProfileType;

    /**
     * @var Collection<int, ContactInterface>
     */
    private $contacts;

    /**
     * @var Collection<int, AccountInterface>
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
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $username
     *
     * @return SocialMediaProfile
     */
    public function setUsername($username)
    {
        // Limit to maximal sql column length.
        $this->username = \substr($username, 0, 255);

        return $this;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('username')]
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return SocialMediaProfile
     */
    public function setSocialMediaProfileType(SocialMediaProfileType $socialMediaProfileType)
    {
        $this->socialMediaProfileType = $socialMediaProfileType;

        return $this;
    }

    /**
     * @return SocialMediaProfileType
     */
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('socialMediaProfileType')]
    #[Groups(['fullAccount', 'fullContact'])]
    public function getSocialMediaProfileType()
    {
        return $this->socialMediaProfileType;
    }

    /**
     * @return SocialMediaProfile
     */
    public function addContact(ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    public function removeContact(ContactInterface $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * @return Collection<int, ContactInterface>
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @return SocialMediaProfile
     */
    public function addAccount(AccountInterface $account)
    {
        $this->accounts[] = $account;

        return $this;
    }

    public function removeAccount(AccountInterface $account)
    {
        $this->accounts->removeElement($account);
    }

    /**
     * @return Collection<int, AccountInterface>
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
