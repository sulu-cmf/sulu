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
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile as SocialMediaProfileEntity;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType as SocialMediaProfileTypeEntity;
use Sulu\Component\Rest\ApiWrapper;

class SocialMediaProfile extends ApiWrapper
{
    public function __construct(SocialMediaProfileEntity $socialMediaProfile, $locale)
    {
        $this->entity = $socialMediaProfile;
        $this->locale = $locale;
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function setUsername(string $username): self
    {
        $this->entity->setUsername($username);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('username')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getUsername(): ?string
    {
        return $this->entity->getUsername();
    }

    public function setSocialMediaProfileType(SocialMediaProfileTypeEntity $socialMediaProfileType): self
    {
        $this->entity->setSocialMediaProfileType($socialMediaProfileType);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('socialMediaType')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getSocialMediaProfileType(): ?int
    {
        return $this->entity->getSocialMediaProfileType()->getId();
    }
}
