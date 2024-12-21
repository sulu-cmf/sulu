<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents media item in media data provider.
 */
#[ExclusionPolicy('all')]
class MediaDataItem implements ItemInterface
{
    public function __construct(private Media $entity)
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
        return $this->entity->getTitle() ?: '';
    }

    #[VirtualProperty]
    public function getImage()
    {
        if (!\array_key_exists('sulu-50x50', $thumbnails = $this->entity->getThumbnails())) {
            return null;
        }

        return $thumbnails['sulu-50x50'];
    }

    public function getResource()
    {
        return $this->entity;
    }
}
