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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class PropertyType
{
    /**
     * @var string
     */
    #[Type('string')]
    private $name;

    /**
     * @var Metadata
     */
    #[Type('Sulu\Component\Content\Compat\Metadata')]
    private $metadata;

    /**
     * properties managed by this block.
     *
     * @var PropertyInterface[]
     */
    #[Type('array<Sulu\Component\Content\Compat\Property>')]
    private $childProperties = [];

    public function __construct($name, $metadata)
    {
        $this->name = $name;
        $this->metadata = new Metadata($metadata);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * returns a list of properties managed by this block.
     *
     * @return PropertyInterface[]
     */
    public function getChildProperties()
    {
        return $this->childProperties;
    }

    /**
     * returns child property with given name.
     *
     * @param string $name
     *
     * @return null|PropertyInterface
     */
    public function getProperty($name)
    {
        foreach ($this->getChildProperties() as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }

        return;
    }

    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }

    /**
     * returns property with given name.
     *
     * @param string $name of property
     *
     * @return PropertyInterface
     *
     * @throws NoSuchPropertyException
     */
    public function getChild($name)
    {
        $propertyNames = [];
        foreach ($this->childProperties as $child) {
            if ($child->getName() === $name) {
                return $child;
            }

            $propertyNames[] = $child->getName();
        }

        throw new NoSuchPropertyException(\sprintf(
            'Property "%s" not found in "%s". Available properties: "%s"',
            $name,
            $this->getName(),
            \implode('", "', $propertyNames)
        ));
    }

    public function __clone()
    {
        $properties = $this->childProperties;
        $this->childProperties = [];
        foreach ($properties as $childProperty) {
            $this->addChild(clone $childProperty);
        }
    }
}
