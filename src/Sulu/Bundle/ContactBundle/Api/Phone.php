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

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\PhoneType as PhoneTypeEntity;
use Sulu\Component\Rest\ApiWrapper;

class Phone extends ApiWrapper
{
    public function __construct(PhoneEntity $phone, $locale)
    {
        $this->entity = $phone;
        $this->locale = $locale;
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function setPhone(string $phone): self
    {
        $this->entity->setPhone($phone);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('phone')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getPhone(): ?string
    {
        return $this->entity->getPhone();
    }

    public function setPhoneType(PhoneTypeEntity $phoneType): self
    {
        $this->entity->setPhoneType($phoneType);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('phoneType')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getPhoneType(): ?int
    {
        return $this->entity->getPhoneType()->getId();
    }
}
