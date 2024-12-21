<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use JMS\Serializer\Annotation\Type;

/**
 * Tag for property.
 */
class PropertyTag
{
    /**
     * name of tag.
     *
     * @var string
     */
    #[Type('string')]
    private $name;

    /**
     * priority of tag.
     *
     * @var int
     */
    #[Type('integer')]
    private $priority;

    /**
     * attributes of the tag.
     *
     * @var array
     */
    #[Type('array')]
    private $attributes = [];

    /**
     * @param string $name
     * @param int $priority
     */
    public function __construct($name, $priority, $attributes = [])
    {
        $this->name = $name;
        $this->priority = $priority;
        $this->attributes = $attributes;
    }

    /**
     * returns name of tag.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns priority of tag.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * returns the attributes of the tag.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
