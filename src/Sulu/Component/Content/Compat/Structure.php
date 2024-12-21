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

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;
use Sulu\Component\Content\Compat\Section\SectionPropertyInterface;
use Sulu\Component\Content\Exception\NoSuchPropertyException;

/**
 * The Structure class represents content structure and is the super type
 * for Page and Snippet classes.
 *
 * Structures are composed of properties which map to content types.
 */
abstract class Structure implements StructureInterface
{
    /**
     * indicates that the node is a content node.
     */
    public const NODE_TYPE_CONTENT = 1;

    /**
     * indicates that the node links to an internal resource.
     */
    public const NODE_TYPE_INTERNAL_LINK = 2;

    /**
     * indicates that the node links to an external resource.
     */
    public const NODE_TYPE_EXTERNAL_LINK = 4;

    /**
     * Structure type page.
     */
    public const TYPE_PAGE = 'page';

    /**
     * Structure type page.
     */
    public const TYPE_SNIPPET = 'snippet';

    /**
     * webspaceKey of node.
     *
     * @var string
     */
    #[Type('string')]
    private $webspaceKey;

    /**
     * languageCode of node.
     *
     * @var string
     */
    #[Type('string')]
    private $languageCode;

    /**
     * unique key of template.
     *
     * @var string
     */
    #[Type('string')]
    private $key;

    /**
     * array of properties.
     *
     * @var array
     */
    #[Type('array<string,Sulu\Component\Content\Compat\Property>')]
    private $properties = [];

    /**
     * has structure sub structures.
     *
     * @var bool
     */
    #[Type('boolean')]
    private $hasChildren = false;

    /**
     * children of node.
     *
     * @var StructureInterface[]
     */
    #[Exclude]
    private $children = null;

    /**
     * uuid of node in CR.
     *
     * @var string
     */
    #[Type('string')]
    private $uuid;

    /**
     * user id of creator.
     *
     * @var int
     */
    #[Type('integer')]
    private $creator;

    /**
     * user id of changer.
     *
     * @var int
     */
    #[Type('integer')]
    private $changer;

    /**
     * datetime of creation.
     *
     * @var \DateTime
     */
    #[Type('DateTime')]
    private $created;

    /**
     * datetime of last changed.
     *
     * @var \DateTime
     */
    #[Type('DateTime')]
    private $changed;

    /**
     * first published.
     *
     * @var \DateTime
     */
    #[Type('DateTime')]
    private $published;

    /**
     * structure translation is valid.
     *
     * @var bool
     */
    #[Type('boolean')]
    private $hasTranslation;

    /**
     * @var StructureType
     */
    #[Type('Sulu\Component\Content\Compat\StructureType')]
    private $type;

    /**
     * @var array
     */
    #[Type('array')]
    private $tags = [];

    /**
     * type of node.
     *
     * @var int
     */
    #[Type('integer')]
    private $nodeType;

    /**
     * indicates internal structure.
     *
     * @var bool
     */
    #[Type('boolean')]
    private $internal;

    /**
     * content node is a shadow for another content.
     *
     * @var bool
     */
    #[Type('boolean')]
    private $isShadow;

    /**
     * when shadow is enabled, this node is a shadow for
     * this language.
     *
     * @var string
     */
    #[Type('string')]
    private $shadowBaseLanguage = '';

    /**
     * the shadows which are activated on this node. Note this is
     * not stored in the phpcr node, it is determined by the content mapper.
     *
     * @var array
     */
    #[Type('array')]
    private $shadowLocales = [];

    /**
     * @var array
     */
    #[Type('array')]
    private $contentLocales = [];

    /**
     * @var Metadata
     */
    #[Type('Sulu\Component\Content\Compat\Metadata')]
    private $metaData;

    /**
     * @var StructureTag[]
     */
    #[Type('array')]
    private $structureTags;

    /**
     * path of node.
     *
     * @var string
     */
    #[Type('string')]
    private $path;

    /**
     * @param string $key
     * @param mixed[] $metaData
     */
    public function __construct($key, $metaData)
    {
        $this->key = $key;

        // default content node-type
        $this->nodeType = self::NODE_TYPE_CONTENT;
        $this->metaData = new Metadata($metaData);
        $this->published = null;
    }

    /**
     * adds a property to structure.
     */
    protected function addChild(PropertyInterface $property)
    {
        if ($property instanceof SectionPropertyInterface) {
            foreach ($property->getChildProperties() as $childProperty) {
                $this->addPropertyTags($childProperty);
            }
        } else {
            $this->addPropertyTags($property);
        }

        $this->properties[$property->getName()] = $property;
    }

    /**
     * add tags of properties.
     */
    protected function addPropertyTags(PropertyInterface $property)
    {
        foreach ($property->getTags() as $tag) {
            if (!\array_key_exists($tag->getName(), $this->tags)) {
                $this->tags[$tag->getName()] = [
                    'tag' => $tag,
                    'properties' => [$tag->getPriority() => $property],
                    'highest' => $property,
                    'lowest' => $property,
                ];
            } else {
                $this->tags[$tag->getName()]['properties'][$tag->getPriority()] = $property;

                // replace highest priority property
                $highestProperty = $this->tags[$tag->getName()]['highest'];
                if ($highestProperty->getTag($tag->getName())->getPriority() < $tag->getPriority()) {
                    $this->tags[$tag->getName()]['highest'] = $property;
                }

                // replace lowest priority property
                $lowestProperty = $this->tags[$tag->getName()]['lowest'];
                if ($lowestProperty->getTag($tag->getName())->getPriority() > $tag->getPriority()) {
                    $this->tags[$tag->getName()]['lowest'] = $property;
                }
            }
        }
    }

    /**
     * @param string $language
     */
    public function setLanguageCode($language)
    {
        $this->languageCode = $language;
    }

    /**
     * returns language of node.
     *
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * @param string $webspace
     */
    public function setWebspaceKey($webspace)
    {
        $this->webspaceKey = $webspace;
    }

    /**
     * returns webspace of node.
     *
     * @return string
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * key of template definition.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * returns uuid of node.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * sets uuid of node.
     *
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * returns id of creator.
     *
     * @return int
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * sets user id of creator.
     *
     * @param int $userId id of creator
     */
    public function setCreator($userId)
    {
        $this->creator = $userId;
    }

    /**
     * returns user id of changer.
     *
     * @return int
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * sets user id of changer.
     *
     * @param int $userId id of changer
     */
    public function setChanger($userId)
    {
        $this->changer = $userId;
    }

    /**
     * return created datetime.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * sets created datetime.
     *
     * @return \DateTime
     */
    public function setCreated(\DateTime $created)
    {
        return $this->created = $created;
    }

    /**
     * returns changed DateTime.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * sets changed datetime.
     */
    public function setChanged(\DateTime $changed)
    {
        $this->changed = $changed;
    }

    /**
     * returns a property instance with given name.
     *
     * @param string $name name of property
     *
     * @return PropertyInterface
     *
     * @throws NoSuchPropertyException
     */
    public function getProperty($name)
    {
        $result = $this->findProperty($name);

        if (null !== $result) {
            return $result;
        } elseif (isset($this->properties[$name])) {
            return $this->properties[$name];
        } else {
            throw new NoSuchPropertyException($name);
        }
    }

    /**
     * returns a property instance with given tag name.
     *
     * @param string $tagName
     * @param bool $highest
     *
     * @return PropertyInterface
     *
     * @throws NoSuchPropertyException
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        if (\array_key_exists($tagName, $this->tags)) {
            return $this->tags[$tagName][true === $highest ? 'highest' : 'lowest'];
        } else {
            throw new NoSuchPropertyException($tagName);
        }
    }

    /**
     * returns properties with given tag name sorted by priority.
     *
     * @param string $tagName
     *
     * @return PropertyInterface
     *
     * @throws NoSuchPropertyException
     */
    public function getPropertiesByTagName($tagName)
    {
        if (\array_key_exists($tagName, $this->tags)) {
            return $this->tags[$tagName]['properties'];
        } else {
            throw new NoSuchPropertyException($tagName);
        }
    }

    /**
     * return value of property with given name.
     *
     * @param string $name name of property
     */
    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }

    /**
     * returns value of property with given tag name.
     *
     * @param string $tagName
     */
    public function getPropertyValueByTagName($tagName)
    {
        return $this->getPropertyByTagName($tagName, true)->getValue();
    }

    /**
     * checks if a property exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProperty($name)
    {
        return null !== $this->findProperty($name);
    }

    /**
     * find property in flatten properties.
     *
     * @param string $name
     *
     * @return null|PropertyInterface
     */
    private function findProperty($name)
    {
        foreach ($this->getProperties(true) as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }

        return;
    }

    /**
     * @param bool $hasChildren
     */
    public function setHasChildren($hasChildren)
    {
        $this->hasChildren = $hasChildren;
    }

    /**
     * @return bool
     */
    public function getHasChildren()
    {
        return $this->hasChildren;
    }

    /**
     * @param StructureInterface[] $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * @return null|StructureInterface[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * returns true if state of site is "published".
     *
     * @return bool
     */
    public function getPublishedState()
    {
        return true;
    }

    /**
     * @param \DateTime $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * returns first published date.
     *
     * @return \DateTime
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param bool $hasTranslation
     */
    public function setHasTranslation($hasTranslation)
    {
        $this->hasTranslation = $hasTranslation;
    }

    /**
     * set if this structure should act like a shadow.
     *
     * @return bool
     */
    public function getIsShadow()
    {
        return $this->isShadow;
    }

    /**
     * set if this node should act like a shadow.
     *
     * @param bool $isShadow
     */
    public function setIsShadow($isShadow)
    {
        $this->isShadow = $isShadow;
    }

    /**
     * return the shadow base language.
     *
     * @return string
     */
    public function getShadowBaseLanguage()
    {
        return $this->shadowBaseLanguage;
    }

    /**
     * set the shadow base language.
     *
     * @param string $shadowBaseLanguage
     */
    public function setShadowBaseLanguage($shadowBaseLanguage)
    {
        $this->shadowBaseLanguage = $shadowBaseLanguage;
    }

    /**
     * return true if structure translation is valid.
     *
     * @return bool
     */
    public function getHasTranslation()
    {
        return $this->hasTranslation;
    }

    /**
     * returns an array of properties.
     *
     * @param bool $flatten
     *
     * @return PropertyInterface[]
     */
    public function getProperties($flatten = false)
    {
        if (false === $flatten) {
            return $this->properties;
        } else {
            $result = [];
            foreach ($this->properties as $property) {
                if ($property instanceof SectionPropertyInterface) {
                    $result = \array_merge($result, $property->getChildProperties());
                } else {
                    $result[] = $property;
                }
            }

            return $result;
        }
    }

    /**
     * returns all property names.
     *
     * @return array
     */
    public function getPropertyNames()
    {
        return \array_keys($this->properties);
    }

    /**
     * @param StructureType $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return StructureType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param int $nodeType
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;
    }

    /**
     * @return bool
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * @param bool $internal
     */
    public function setInternal($internal)
    {
        $this->internal = $internal;
    }

    public function getNodeName()
    {
        if ($this->hasProperty('title')) {
            return $this->getPropertyValue('title');
        }

        return '';
    }

    public function hasTag($tag)
    {
        return \array_key_exists($tag, $this->tags);
    }

    public function getLocalizedTitle($languageCode)
    {
        $default = \ucfirst($this->key);
        if ($this->metaData) {
            return $this->metaData->get('title', $languageCode, $default);
        } else {
            return $default;
        }
    }

    /**
     * magic getter.
     *
     * @param string $property name of property
     *
     * @throws NoSuchPropertyException
     */
    public function __get($property)
    {
        if (\method_exists($this, 'get' . \ucfirst($property))) {
            return $this->{'get' . \ucfirst($property)}();
        } else {
            return $this->getProperty($property)->getValue();
        }
    }

    /**
     * magic setter.
     *
     * @param string $property name of property
     * @param mixed $value value
     *
     * @throws NoSuchPropertyException
     */
    public function __set($property, $value)
    {
        if (isset($this->properties[$property])) {
            $this->getProperty($property)->setValue($value);
        } else {
            throw new NoSuchPropertyException($property);
        }
    }

    /**
     * magic isset.
     *
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        if (null !== $this->findProperty($property)) {
            return true;
        } else {
            return isset($this->$property);
        }
    }

    /**
     * returns an array of property value pairs.
     *
     * @param bool $complete True if result should be representation of full node
     *
     * @return array
     */
    public function toArray($complete = true)
    {
        if ($complete) {
            $result = [
                'id' => $this->uuid,
                'nodeType' => $this->nodeType,
                'internal' => $this->internal,
                'shadowLocales' => $this->getShadowLocales(),
                'contentLocales' => $this->getContentLocales(),
                'shadowOn' => $this->getIsShadow(),
                'shadowBaseLanguage' => $this->getShadowBaseLanguage() ?: false,
                'template' => $this->getKey(),
                'hasSub' => $this->hasChildren,
                'creator' => $this->creator,
                'changer' => $this->changer,
                'created' => $this->created,
                'changed' => $this->changed,
            ];

            if (null !== $this->type) {
                $result['type'] = $this->getType()->toArray();
            }

            if (self::NODE_TYPE_INTERNAL_LINK === $this->nodeType) {
                $result['linked'] = 'internal';
            } elseif (self::NODE_TYPE_EXTERNAL_LINK === $this->nodeType) {
                $result['linked'] = 'external';
            }

            $this->appendProperties($this->getProperties(), $result);

            return $result;
        } else {
            $result = [
                'id' => $this->uuid,
                'path' => $this->path,
                'nodeType' => $this->nodeType,
                'internal' => $this->internal,
                'getContentLocales' => $this->getContentLocales(),
                'hasSub' => $this->hasChildren,
                'title' => $this->getProperty('title')->toArray(),
            ];

            if (null !== $this->type) {
                $result['type'] = $this->getType()->toArray();
            }

            if (self::NODE_TYPE_INTERNAL_LINK === $this->nodeType) {
                $result['linked'] = 'internal';
            } elseif (self::NODE_TYPE_EXTERNAL_LINK === $this->nodeType) {
                $result['linked'] = 'external';
            }

            return $result;
        }
    }

    private function appendProperties($properties, &$array)
    {
        /** @var PropertyInterface $property */
        foreach ($properties as $property) {
            if ($property instanceof SectionPropertyInterface) {
                $this->appendProperties($property->getChildProperties(), $array);
            } else {
                $array[$property->getName()] = $property->toArray();
            }
        }
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON.
     *
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * return available shadow languages on this structure
     * (determined at runtime).
     *
     * @return array
     */
    public function getShadowLocales()
    {
        return $this->shadowLocales;
    }

    /**
     * set the available enabled shadow languages.
     *
     * @param array $shadowLocales
     */
    public function setShadowLocales($shadowLocales)
    {
        $this->shadowLocales = $shadowLocales;
    }

    /**
     * Return the available concrete languages (i.e.
     * the languages which are set and are not shadows).
     *
     * @return array
     */
    public function getContentLocales()
    {
        return \array_values($this->contentLocales);
    }

    /**
     * Set the content languages (note this should only be done internally).
     *
     * @param array $contentLocales
     */
    public function setContentLocales($contentLocales)
    {
        $this->contentLocales = $contentLocales;
    }

    /**
     * Add a tag to this structure.
     */
    public function addStructureTag(StructureTag $structureTag)
    {
        $this->structureTags[$structureTag->getName()] = $structureTag;
    }

    /**
     * Return true if this structure has the given tag.
     *
     * @return bool
     */
    public function hasStructureTag($name)
    {
        return isset($this->structureTags[$name]);
    }

    /**
     * Return the tag with the given name.
     *
     * @param string $name
     *
     * @return StructureTag
     *
     * @throws \InvalidArgumentException
     */
    public function getStructureTag($name)
    {
        if (!isset($this->structureTags[$name])) {
            throw new \InvalidArgumentException(\sprintf('Trying to get undefined structure StructureTag "%s"', $name));
        }

        return $this->structureTags[$name];
    }

    public function getNodeState()
    {
        return self::STATE_PUBLISHED;
    }

    /**
     * returns absolute path of node.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    public function copyFrom(StructureInterface $structure)
    {
        $this->setWebspaceKey($structure->getWebspaceKey());
        $this->setLanguageCode($structure->getLanguageCode());
        $this->setUuid($structure->getUuid());

        $this->setChanged($structure->getChanged());
        $this->setChanger($structure->getChanger());
        $this->setCreated($structure->getCreated());
        $this->setCreator($structure->getCreator());

        $this->setPublished($structure->getPublished());
        $this->setPath($structure->getPath());
        $this->setNodeType($structure->getNodeType());
        $this->setHasTranslation($structure->getHasTranslation());
    }
}
