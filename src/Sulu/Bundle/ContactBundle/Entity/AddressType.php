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
 * AddressType.
 */
class AddressType implements \JsonSerializable
{
    /**
     * @var string
     */
    #[Groups(['frontend', 'fullAccount', 'fullContact'])]
    private $name;

    /**
     * @var int
     */
    #[Groups(['frontend', 'fullAccount', 'fullContact'])]
    private $id;

    /**
     * @var Collection<int, Address>
     */
    #[Exclude]
    private $addresses;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    /**
     * To force id = 1 in load fixtures.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return AddressType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Add addresses.
     *
     * @return AddressType
     */
    public function addAddresse(Address $addresses)
    {
        $this->addresses[] = $addresses;

        return $this;
    }

    /**
     * Remove addresses.
     */
    public function removeAddresse(Address $addresses)
    {
        $this->addresses->removeElement($addresses);
    }

    /**
     * Get addresses.
     *
     * @return Collection<int, Address>
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON.
     *
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }

    /**
     * Add addresses.
     *
     * @return AddressType
     */
    public function addAddress(Address $addresses)
    {
        $this->addresses[] = $addresses;

        return $this;
    }

    /**
     * Remove addresses.
     */
    public function removeAddress(Address $addresses)
    {
        $this->addresses->removeElement($addresses);
    }
}
