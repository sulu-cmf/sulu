<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\Exclude;

/**
 * ContactAddress.
 */
class ContactAddress
{
    /**
     * @var bool
     */
    private $main;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Address
     */
    private $address;

    /**
     * @var ContactInterface
     * @Exclude
     */
    private $contact;

    /**
     * Set main.
     *
     * @param bool $main
     *
     * @return ContactAddress
     */
    public function setMain($main)
    {
        $this->main = $main;

        return $this;
    }

    /**
     * Get main.
     *
     * @return bool
     */
    public function getMain()
    {
        return $this->main;
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
     * Set address.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Address $address
     *
     * @return ContactAddress
     */
    public function setAddress(\Sulu\Bundle\ContactBundle\Entity\Address $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set contact.
     *
     * @param ContactInterface $contact
     *
     * @return ContactAddress
     */
    public function setContact(ContactInterface $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return ContactInterface
     */
    public function getContact()
    {
        return $this->contact;
    }
}
