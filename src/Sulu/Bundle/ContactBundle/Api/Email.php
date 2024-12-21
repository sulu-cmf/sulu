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
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\EmailType as EmailTypeEntity;
use Sulu\Component\Rest\ApiWrapper;

class Email extends ApiWrapper
{
    public function __construct(EmailEntity $email, $locale)
    {
        $this->entity = $email;
        $this->locale = $locale;
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function setEmail(string $email): self
    {
        $this->entity->setEmail($email);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('email')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getEmail(): ?string
    {
        return $this->entity->getEmail();
    }

    public function setEmailType(EmailTypeEntity $emailType): self
    {
        $this->entity->setEmailType($emailType);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('emailType')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getEmailType(): ?int
    {
        return $this->entity->getEmailType()->getId();
    }
}
