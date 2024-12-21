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
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The ContactAddress class which will be exported to the API.
 */
#[ExclusionPolicy('all')]
class ContactAddress extends ApiWrapper
{
    public function __construct(ContactAddressEntity $address)
    {
        $this->entity = $address;
    }

    /**
     * Returns the id of the product.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set main.
     *
     * @param bool $main
     *
     * @return ContactAddress
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
    public function getMain()
    {
        return $this->entity->getMain();
    }

    /**
     * Set address.
     *
     * @return ContactAddress
     */
    public function setAddress(AddressEntity $address)
    {
        $this->entity->setAddress($address);

        return $this;
    }

    /**
     * Get address.
     *
     * @return AddressEntity
     */
    #[VirtualProperty]
    #[SerializedName('address')]
    public function getAddress()
    {
        return $this->entity->getAddress();
    }
}
