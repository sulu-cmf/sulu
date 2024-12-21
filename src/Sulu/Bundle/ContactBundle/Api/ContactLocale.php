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
use Sulu\Bundle\ContactBundle\Entity\ContactLocale as ContactLocaleEntity;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The ContactLocale class which will be exported to the API.
 */
#[ExclusionPolicy('all')]
class ContactLocale extends ApiWrapper
{
    public function __construct(ContactLocaleEntity $contactLocale)
    {
        $this->entity = $contactLocale;
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
     * Set locale.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->entity->setLocale($locale);

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('locale')]
    public function getLocale()
    {
        return $this->entity->getLocale();
    }
}
