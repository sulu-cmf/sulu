<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The AccountContact class which will be exported to the API.
 */
#[ExclusionPolicy('all')]
class AccountContact extends ApiWrapper
{
    /**
     * @param string $locale The locale of this product
     */
    public function __construct(AccountContactEntity $accountContact, $locale)
    {
        $this->entity = $accountContact;
        $this->locale = $locale;
    }

    /**
     * Set main.
     *
     * @param bool $main
     *
     * @return AccountContact
     */
    public function setMain($main)
    {
        $this->entity->setMain($main);

        return $this;
    }

    /**
     * Get main.
     *
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('main')]
    #[Groups(['fullAccount'])]
    public function getMain()
    {
        return $this->entity->getMain();
    }

    /**
     * Get id.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullAccount'])]
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set contact.
     *
     * @return AccountContact
     */
    public function setContact(ContactEntity $contact)
    {
        $this->entity->setContact($contact);

        return $this;
    }

    /**
     * Get contact.
     *
     * @return ContactEntity
     */
    #[VirtualProperty]
    #[SerializedName('contact')]
    #[Groups(['fullAccount'])]
    public function getContact()
    {
        $contact = $this->entity->getContact();

        return [
            'id' => $contact->getId(),
            'fullName' => $contact->getFullName(),
        ];
    }

    /**
     * Set account.
     *
     * @return AccountContact
     */
    public function setAccount(AccountInterface $account)
    {
        $this->entity->setAccount($account);

        return $this;
    }

    /**
     * Get account.
     *
     * @return Account
     */
    #[VirtualProperty]
    #[SerializedName('account')]
    #[Groups(['fullAccount'])]
    public function getAccount()
    {
        $account = $this->entity->getAccount();

        return [
            'id' => $account->getId(),
            'name' => $account->getName(),
        ];
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return AccountContact
     */
    public function setPosition($position)
    {
        $this->entity->setPosition($position);

        return $this;
    }

    /**
     * Get position.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('position')]
    #[Groups(['fullAccount'])]
    public function getPosition()
    {
        return $this->entity->getPosition();
    }
}
