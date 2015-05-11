<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Section;

use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\HandlerCallback;

/**
 * defines a section for properties
 * @package Sulu\Component\Content
 */
class SectionProperty extends Property implements SectionPropertyInterface
{
    /**
     * properties managed by this block
     * @var PropertyInterface[]
     * @Type("array<Sulu\Component\Content\Property>")
     */
    private $childProperties = array();

    /**
     * @param string $name
     * @param array $metadata
     * @param string $col
     */
    public function __construct($name, $metadata, $col)
    {
        parent::__construct($name, $metadata, 'section', false, false, 1, 1, array(), array(), $col);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildProperties()
    {
        return $this->childProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }

    /**
     * @HandlerCallback("json", direction = "serialization")
     */
    public function serializeToJson(JsonSerializationVisitor $visitor, $data, Context $context)
    {
        return parent::serializeToJson($visitor, $data, $context);
    }

    /**
     * @HandlerCallback("json", direction = "deserialization")
     */
    public function deserializeToJson(JsonDeserializationVisitor $visitor, $data, Context $context)
    {
        return parent::deserializeToJson($visitor, $data, $context);
    }
}
