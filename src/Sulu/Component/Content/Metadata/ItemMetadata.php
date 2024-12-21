<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Base class for all structure related metadata classes.
 *
 * @deprecated use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata instead
 */
abstract class ItemMetadata
{
    /**
     * The title of this property|structure e.g. [["de": "Artikles", "en": "Articles"]].
     *
     * @var array
     */
    protected $titles = [];

    /**
     * Description of this property|structure e.g. [["de": "Liste von Artikeln", "en": "List of articles"]].
     *
     * @var array
     */
    protected $descriptions = [];

    /**
     * Tags, e.g.
     *
     * ````
     * array(
     *     array('name' => 'sulu_search.field', 'type' => 'string')
     * )
     * ````
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Parameters applying to the property.
     *
     * ````
     * array(
     *     'placeholder' => 'Enter some text',
     * )
     * ````
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Children of this item, f.e. properties, sections or structures.
     *
     * @var ItemMetadata[]
     */
    protected $children = [];

    /**
     * @var string
     */
    protected $disabledCondition = null;

    /**
     * @var string
     */
    protected $visibleCondition = null;

    /**
     * @param string|int|float $name
     */
    public function __construct(
        protected $name = null
    ) {
    }

    public function __get($name)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.0',
            'Do not use public property "%s" from "%s"',
            $name,
            __CLASS__);

        return $this[$name];
    }

    public function __set($name, $value)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.0',
            'Do not use public property "%s" from "%s"',
            $name,
            __CLASS__
        );

        $this[$name] = $value;
    }

    /**
     * Set the name of the metadata property which can also be a int or float value.
     *
     * @param string|int|float $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setTitles(array $titles): self
    {
        $this->titles = $titles;

        return $this;
    }

    public function getTitles(): array
    {
        return $this->titles;
    }

    public function setDescriptions(array $descriptions): self
    {
        $this->descriptions = $descriptions;

        return $this;
    }

    public function getDescriptions(): array
    {
        return $this->descriptions;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param array{name: string, attributes?: array<string, mixed>} $tag
     */
    public function addTag(array $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Return the named property.
     *
     * @param string $name
     *
     * @return ItemMetadata
     */
    public function getChild($name)
    {
        if (!isset($this->children[$name])) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown child "%s" in structure "%s". Children: "%s"',
                $name, $this->name, \implode('", "', \array_keys($this->children))
            ));
        }

        return $this->children[$name];
    }

    /**
     * Return true if this structure has the named property, false
     * if it does not.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasChild($name)
    {
        return isset($this->children[$name]);
    }

    /**
     * Adds a child item.
     */
    public function addChild(self $child)
    {
        if (isset($this->children[$child->name])) {
            throw new \InvalidArgumentException(\sprintf(
                'Child with key "%s" already exists',
                $child->name
            ));
        }

        $this->children[$child->name] = $child;
    }

    /**
     * Return the children of this item.
     *
     * @return ItemMetadata[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set the children of this item.
     *
     * @param ItemMetadata[] $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Return the localized name of this ItemMetadata.
     *
     * @param string $locale Localization
     *
     * @return string
     */
    public function getTitle($locale)
    {
        if (isset($this->titles[$locale])) {
            return $this->titles[$locale];
        }
    }

    /**
     * Return the parameter with the given name.
     *
     * @return null|mixed
     */
    public function getParameter(string $name)
    {
        foreach ($this->parameters as $parameter) {
            if (!\is_array($parameter)) {
                continue;
            }

            if (!isset($parameter['name'])) {
                continue;
            }

            if ($parameter['name'] === $name) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * Return the name of this item.
     *
     * @return string|int|float
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the tags of this item.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Return the named tag.
     *
     * @param string $tagName
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getTag($tagName)
    {
        foreach ($this->tags as $tag) {
            if ($tag['name'] === $tagName) {
                return $tag;
            }
        }

        throw new \InvalidArgumentException(\sprintf(
            'Unknown tag "%s"', $tagName
        ));
    }

    /**
     * Return true if this item has the named tag.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTag($name)
    {
        foreach ($this->tags as $tag) {
            if ($tag['name'] == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the parameters for this property.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return the description of this property.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getDescription($locale)
    {
        if (isset($this->descriptions[$locale])) {
            return $this->descriptions[$locale];
        }

        return '';
    }

    public function getDisabledCondition(): ?string
    {
        return $this->disabledCondition;
    }

    public function setDisabledCondition(?string $disabledCondition): self
    {
        $this->disabledCondition = $disabledCondition;

        return $this;
    }

    public function getVisibleCondition(): ?string
    {
        return $this->visibleCondition;
    }

    public function setVisibleCondition(?string $visibleCondition): self
    {
        $this->visibleCondition = $visibleCondition;

        return $this;
    }

    public function __clone()
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = clone $child;
        }
        $this->children = $children;
    }
}
