<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure\Loader;

use Exception;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Util\XmlUtils;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Structure\Item;
use Sulu\Component\Content\Structure\Section;
use Sulu\Component\Content\Structure\Block\BlockProperty;
use Sulu\Component\Content\Structure\Block\BlockPropertyType;
use Sulu\Component\Content\Structure\Block;
use Sulu\Component\Content\Structure\Component;

/**
 * Load structure structure from an XML file
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class XmlLoader extends XmlLegacyLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $data = parent::load($resource, $type);
        $data = $this->normalizeStructureData($data);

        $structure = new Structure();
        $structure->name = $data['key'];
        $structure->cacheLifetime = $data['cacheLifetime'];
        $structure->controller = $data['controller'];
        $structure->view = $data['view'];
        $structure->tags = $data['tags'];
        $structure->parameters = $data['params'];
        $structure->resource = $resource;
        $this->mapMeta($structure, $data['meta']);

        foreach ($data['properties'] as $propertyName => $dataProperty) {
            $structure->children[$propertyName] = $this->createProperty($propertyName, $dataProperty);
        }

        $structure->burnModelRepresentation();

        return $structure;
    }

    private function createProperty($propertyName, $propertyData)
    {
        if ($propertyData['type'] === 'block') {
            return $this->createBlock($propertyName, $propertyData);
        }

        if ($propertyData['type'] === 'section') {
            return $this->createSection($propertyName, $propertyData);
        }

        $property = new Property();
        $property->name = $propertyName;
        $this->mapProperty($property, $propertyData);

        return $property;
    }

    private function createSection($propertyName, $data)
    {
        $section = new Section();
        $section->name = $propertyName;

        foreach ($data['properties'] as $name => $property) {
            $section->children[$name] = $this->createProperty($name, $property);
        }

        return $section;
    }

    private function createBlock($propertyName, $data)
    {
        $blockProperty = new Block();
        $blockProperty->name = $propertyName;
        $blockProperty->defaultComponentName = $data['default-type'];
        $this->mapProperty($blockProperty, $data);

        foreach ($data['types'] as $name => $type) {
            $component = new Component();
            $component->name = $name;
            foreach ($type['properties'] as $propertyName => $propertyData) {
                $property = new Property();
                $property->name = $propertyName;
                $this->mapProperty($property, $propertyData);
                $component->addChild($property);
            }
            $blockProperty->addComponent($component);
        }

        return $blockProperty;
    }

    private function mapProperty(Property $property, $data)
    {
        $data = $this->normalizePropertyData($data);
        $property->type = $data['type'];
        $property->localized = $data['multilingual'];
        $property->required = $data['mandatory'];
        $property->colSpan = $data['colspan'];
        $property->cssClass = $data['cssClass'];
        $property->tags = $data['tags'];
        $property->minOccurs = $data['minOccurs'] ? : 1;
        $property->maxOccurs = $data['maxOccurs'] ? : 999;
        $property->parameters = $data['params'];
        $this->mapMeta($property, $data['meta']);
    }

    private function normalizePropertyData($data)
    {
        $data = array_replace_recursive(array(
            'type' => null,
            'multilingual' => true,
            'mandatory' => true,
            'colSpan' => null,
            'cssClass' => null,
            'minOccurs' => null,
            'maxOccurs' => null,
        ), $this->normalizeItem($data));

        return $data;
    }

    private function normalizeStructureData($data)
    {
        $data = array_replace_recursive(array(
            'key' => null,
            'view' => null,
            'controller' => null,
            'cacheLifetime' => null,
        ), $this->normalizeItem($data));


        return $data;
    }

    private function normalizeItem($data)
    {
        $data = array_merge_recursive(array(
            'meta' => array(
                'title' => array(),
                'info_text' => array(),
                'placeholder' => array(),
            ),
            'params' => array(),
            'tags' => array(),
        ), $data);

        return $data;
    }

    private function mapMeta($item, $meta)
    {
        $item->title = $meta['title'];
        $item->description = $meta['info_text'];

        if (isset($item->placeholder)) {
            $item->placeholder = $meta['info_text'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        throw new FeatureNotImplementedException();
    }
}
