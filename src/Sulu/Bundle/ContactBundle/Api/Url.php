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
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\ContactBundle\Entity\UrlType as UrlTypeEntity;
use Sulu\Component\Rest\ApiWrapper;

class Url extends ApiWrapper
{
    public function __construct(UrlEntity $url, $locale)
    {
        $this->entity = $url;
        $this->locale = $locale;
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function setUrl(string $url): self
    {
        $this->entity->setUrl($url);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('website')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getUrl(): ?string
    {
        return $this->entity->getUrl();
    }

    public function setUrlType(UrlTypeEntity $urlType): self
    {
        $this->entity->setUrlType($urlType);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('websiteType')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getUrlType(): ?int
    {
        return $this->entity->getUrlType()->getId();
    }
}
