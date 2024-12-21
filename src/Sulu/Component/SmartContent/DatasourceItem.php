<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Concrete Datasource item.
 */
#[ExclusionPolicy('all')]
class DatasourceItem implements DatasourceItemInterface
{
    /**
     * @param string|int $id
     * @param string $title
     * @param string $path
     * @param string|null $image
     */
    public function __construct(private $id, private $title, private $path, private $image = null)
    {
    }

    /**
     * @return int|string
     */
    #[VirtualProperty]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    public function getImage()
    {
        return $this->image;
    }
}
