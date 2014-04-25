<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Property of Structure generated from Structure Manager to map a template
 */
class Property implements PropertyInterface, \JsonSerializable
{
    /**
     * name of property
     * @var string
     */
    private $name;

    /**
     * @var string title of property
     */
    private $title;

    /**
     * is property mandatory
     * @var bool
     */
    private $mandatory;

    /**
     * is property multilingual
     * @var bool
     */
    private $multilingual;

    /**
     * min occurs of property value
     * @var int
     */
    private $minOccurs;

    /**
     * max occurs of property value
     * @var int
     */
    private $maxOccurs;

    /**
     * name of content type
     * @var string
     */
    private $contentTypeName;

    /**
     * parameter of property to merge with parameter of content type
     * @var array
     */
    private $params;

    /**
     * tags defined in xml
     * @var PropertyTag[]
     */
    private $tags;

    /**
     * value of property
     * @var mixed
     */
    private $value;

    function __construct(
        $name,
        $title,
        $contentTypeName,
        $mandatory = false,
        $multilingual = false,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = array(),
        $tags = array()
    )
    {
        $this->contentTypeName = $contentTypeName;
        $this->mandatory = $mandatory;
        $this->maxOccurs = $maxOccurs;
        $this->minOccurs = $minOccurs;
        $this->multilingual = $multilingual;
        $this->name = $name;
        $this->title = $title;
        $this->params = $params;
        $this->tags =$tags;
    }

    /**
     * returns name of template
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns mandatory
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * returns multilingual
     * @return bool
     */
    public function isMultilingual()
    {
        return $this->multilingual;
    }

    /**
     * return min occurs
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    /**
     * return max occurs
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * returns field is mandatory
     * @return boolean
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * returns field is multilingual
     * @return boolean
     */
    public function getMultilingual()
    {
        return $this->multilingual;
    }

    /**
     * returns tags defined in xml
     * @return \Sulu\Component\Content\PropertyTag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * returns tag with given name
     * @param string $tagName
     * @return PropertyTag
     */
    public function getTag($tagName)
    {
        return $this->tags[$tagName];
    }

    /**
     * returns title of property
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * sets the value from property
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * gets the value from property
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * returns name of content type
     * @return string
     */
    public function getContentTypeName()
    {
        return $this->contentTypeName;
    }

    /**
     * parameter of property
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * returns TRUE if property is a block
     * @return boolean
     */
    public function getIsBlock()
    {
        return false;
    }

    /**
     * returns TRUE if property is multiple
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->minOccurs > 1 || $this->maxOccurs > 1;
    }

    /**
     * magic getter for twig templates
     * @param $property
     * @return null
     */
    public function __get($property)
    {
        if (method_exists($this, 'get' . ucfirst($property))) {
            return $this->{'get' . ucfirst($property)}();
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $result = array(
            'name' => $this->getName(),
            'title' => $this->getTitle(),
            'mandatory' => $this->getMandatory(),
            'multilingual' => $this->getMultilingual(),
            'minOccurs' => $this->getMinOccurs(),
            'maxOccurs' => $this->getMaxOccurs(),
            'contentTypeName' => $this->getContentTypeName(),
            'params' => $this->getParams(),
            'tags' => array()
        );
        foreach ($this->getTags() as $tag) {
            $result['tags'][] = array(
                'name' => $tag->getName(),
                'priority' => $tag->getPriority()
            );
        }

        return $result;
    }
}
