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
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\FaxType as FaxTypeEntity;
use Sulu\Component\Rest\ApiWrapper;

class Fax extends ApiWrapper
{
    public function __construct(FaxEntity $fax, $locale)
    {
        $this->entity = $fax;
        $this->locale = $locale;
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function setFax(string $fax): self
    {
        $this->entity->setFax($fax);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('fax')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getFax(): ?string
    {
        return $this->entity->getFax();
    }

    public function setFaxType(FaxTypeEntity $faxType): self
    {
        $this->entity->setFaxType($faxType);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('faxType')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getFaxType(): ?int
    {
        return $this->entity->getFaxType()->getId();
    }
}
