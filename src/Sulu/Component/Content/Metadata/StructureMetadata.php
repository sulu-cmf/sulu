<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Exception\NoSuchPropertyException;

/**
 * Represents metadata for a structure.
 *
 * TODO: resource, cacheLifetime and view should be removed. They
 *       should instead be options.
 */
class StructureMetadata extends ItemMetadata
{
    /**
     * The resource from which this structure was loaded
     * (useful for debugging)
     * @var string
     */
    public $resource;

    /**
     * @var string
     */
    public $cacheLifetime;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $view;

    /**
     * Same as ItemMetadata::$children but without Sections
     * @see StructureMetadata::burnModelRepresentation()
     * @var array
     */
    public $properties;

    /**
     * Return a model property.
     *
     * @see StructureMetadata::getProperties()
     *
     * @param string $name
     * @return PropertyMetadata
     */
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown model property "%s", in structure "%s". Known model properties: "%s". Loaded from "%s"',
                $name, $this->getName(), implode('", "', array_keys($this->properties)),
                $this->resource
            ));
        }

        return $this->properties[$name];
    }

    /**
     * Return all model properties
     *
     * The "model" set of properties does not include UI elements 
     * such as sections.
     *
     * @return PropertyMetadata[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Populate the $modelProperties property with only those propertires
     * which are not related to the UI (i.e. the sections).
     *
     * This should be called once after creating the structure and (therefore
     * before writing to the cache
     */
    public function burnModelRepresentation()
    {
        $properties = array();
        foreach ($this->children as $child) {
            if ($child instanceof SectionMetadata) {
                $properties = array_merge($properties, $child->getChildren());
                continue;
            }

            $properties[$child->name] = $child;
        }

        $this->properties = $properties;
    }

    /**
     * Return true if a property with the given name exists.
     *
     * @return bool
     */
    public function hasProperty($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Return true if the structure contains a property with the given
     * tag name.
     *
     * @param string $tagName
     * @return bool
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        $properties = $this->getPropertiesByTagName($tagName);

        if (!$properties) {
            throw new \InvalidArgumentException(sprintf(
                'No property with tag "%s" exists. In structure "%s" loaded from "%s"',
                $tagName, $this->name, $this->resource
            ));
        }

        return reset($properties);
    }

    /**
     * Return true if the structure contains a property with the given
     * tag name.
     *
     * @param string $tagName
     * @return bool
     */
    public function hasPropertyWithTagName($tagName)
    {
        return (boolean) count($this->getPropertiesByTagName($tagName));
    }

    /**
     * Return all properties with the given tag name
     *
     * @param string $tagName
     * @return bool
     */
    public function getPropertiesByTagName($tagName)
    {
        $properties = array();

        foreach ($this->properties as $property) {
            foreach ($property->tags as $tag) {
                if ($tag['name'] == $tagName){
                    $properties[$property->name] = $property;
                }
            }
        }

        return $properties;
    }

    /**
     * Return the resource from which this structure was loaded
     *
     * @return string
     */
    public function getResource() 
    {
        return $this->resource;
    }
}
