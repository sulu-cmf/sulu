<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Section;

use JMS\Serializer\Annotation\Type;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Defines a section for properties.
 */
class SectionProperty extends Property implements SectionPropertyInterface
{
    /**
     * properties managed by this block.
     *
     * @var PropertyInterface[]
     */
    #[Type('array<Sulu\Component\Content\Compat\Property>')]
    private $childProperties = [];

    /**
     * @param string $name
     * @param array $metadata
     * @param int $col
     */
    public function __construct($name, $metadata, $col)
    {
        parent::__construct($name, $metadata, 'section', false, false, 1, 1, [], [], $col);
    }

    public function getChildProperties()
    {
        return $this->childProperties;
    }

    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }
}
