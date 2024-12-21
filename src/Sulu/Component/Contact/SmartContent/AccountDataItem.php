<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents account item in contact data provider.
 */
#[ExclusionPolicy('all')]
class AccountDataItem implements ItemInterface
{
    public function __construct(private Account $entity)
    {
    }

    #[VirtualProperty]
    public function getId()
    {
        return $this->entity->getId();
    }

    #[VirtualProperty]
    public function getTitle()
    {
        return $this->entity->getName();
    }

    #[VirtualProperty]
    public function getImage()
    {
        return null;
    }

    public function getResource()
    {
        return $this->entity;
    }
}
