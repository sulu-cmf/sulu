<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Class Collection
 * The Collection RestObject is the api entity for the CollectionController.
 */
#[ExclusionPolicy('all')]
class Collection extends ApiWrapper
{
    /**
     * @var array
     */
    protected $preview = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var Collection[]
     */
    protected $children = null;

    /**
     * @var Collection
     */
    protected $parent;

    /**
     * @var array
     */
    protected $breadcrumb;

    /**
     * @var CollectionInterface
     */
    protected $entity;

    /**
     * @var int
     */
    protected $mediaCount = 0;

    /**
     * @var int
     */
    protected $subCollectionCount = 0;

    public function __construct(CollectionInterface $collection, $locale)
    {
        $this->entity = $collection;
        $this->locale = $locale;
    }

    /**
     * Set children of resource.
     *
     * @param Collection[] $children
     */
    public function setChildren($children)
    {
        // FIXME remove cache for children and generate then on the fly
        //       reason: preview images cannot be generated without a service
        $this->children = $children;
    }

    /**
     * Add child to resource.
     */
    public function addChild(self $child)
    {
        if (!\is_array($this->children)) {
            $this->children = [];
        }

        $this->children[] = $child;
    }

    /**
     * Returns children of resource.
     *
     * @return Collection[]
     */
    public function getChildren()
    {
        // FIXME remove cache for children and generate then on the fly
        //       reason: preview images cannot be generated without a service
        return $this->children;
    }

    /**
     * @internal
     */
    #[VirtualProperty]
    #[SerializedName('_embedded')]
    public function getEmbedded(): array
    {
        return [
            'breadcrumb' => $this->breadcrumb,
            'parent' => $this->parent,
            'collections' => $this->children,
        ];
    }

    /**
     * Indicates if sub collections exists.
     *
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('hasChildren')]
    public function getHasChildren()
    {
        return $this->subCollectionCount > 0;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->getMeta(true)->setDescription($description);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('description')]
    public function getDescription()
    {
        $meta = $this->getMeta();
        if ($meta) {
            return $meta->getDescription();
        }

        return null;
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @param Collection $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        if (null !== $parent) {
            $this->entity->setParent($parent->getEntity());
        } else {
            $this->entity->setParent(null);
        }

        // FIXME remove cache for parent and generate it on the fly
        //       reason: preview images cannot be generated without a service
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getParent()
    {
        // FIXME remove cache for parent and generate it on the fly
        //       reason: preview images cannot be generated without a service
        return $this->parent;
    }

    /**
     * @return array
     */
    public function getBreadcrumb()
    {
        return $this->breadcrumb;
    }

    /**
     * @param array $breadcrumb
     */
    public function setBreadcrumb($breadcrumb)
    {
        $this->breadcrumb = $breadcrumb;
    }

    /**
     * @param array|string|null $style
     *
     * @return $this
     */
    public function setStyle($style)
    {
        if (!\is_string($style)) {
            $style = \json_encode($style) ?: null;
        }
        $this->entity->setStyle($style);

        return $this;
    }

    /**
     * @return array|null
     */
    #[VirtualProperty]
    #[SerializedName('style')]
    public function getStyle()
    {
        $style = $this->entity->getStyle();

        if (!$style) {
            return null;
        }

        return \json_decode($style, true);
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('properties')]
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('preview')]
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param array $preview
     *
     * @return $this
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('locale')]
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->getMeta(true)->setTitle($title);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('title')]
    public function getTitle()
    {
        $meta = $this->getMeta();
        if ($meta) {
            return $meta->getTitle();
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->entity->setKey($key);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('key')]
    public function getKey()
    {
        return $this->entity->getKey();
    }

    /**
     * @param CollectionType $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->entity->setType($type);

        return $this;
    }

    /**
     * @return CollectionType
     */
    #[VirtualProperty]
    #[SerializedName('type')]
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('changed')]
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * @param UserInterface $changer
     *
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->entity->setChanger($changer);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getChanger()
    {
        $user = $this->entity->getChanger();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('created')]
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * @param UserInterface $creator
     *
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->entity->setCreator($creator);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreator()
    {
        $user = $this->entity->getCreator();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @return int The number of media contained by the collection
     */
    #[VirtualProperty]
    public function getMediaCount()
    {
        return $this->mediaCount;
    }

    /**
     * @param int $mediaCount The new number of media
     */
    public function setMediaCount($mediaCount)
    {
        $this->mediaCount = $mediaCount;
    }

    /**
     * @return int The number of sub collections contained by the collection
     */
    #[VirtualProperty]
    public function getSubCollectionCount()
    {
        return $this->subCollectionCount;
    }

    /**
     * @param int $subCollectionCount The new number of sub collections
     */
    public function setSubCollectionCount($subCollectionCount)
    {
        $this->subCollectionCount = $subCollectionCount;
    }

    /**
     * @return int
     */
    #[VirtualProperty] // Returns the total number of all types of sub objects of this collection.
    public function getObjectCount()
    {
        return $this->getMediaCount() + $this->getSubCollectionCount();
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('locked')]
    public function getLocked()
    {
        return !$this->entity->getType()
            || SystemCollectionManagerInterface::COLLECTION_TYPE === $this->entity->getType()->getKey();
    }

    /**
     * @param bool $create
     *
     * @return CollectionMeta
     */
    private function getMeta($create = false)
    {
        $locale = $this->locale;
        $metaCollection = $this->entity->getMeta();

        // get meta only with this locale
        $metaCollectionFiltered = $metaCollection->filter(
            function($meta) use ($locale) {
                /** @var CollectionMeta $meta */
                if ($meta->getLocale() == $locale) {
                    return true;
                }

                return false;
            }
        );

        // check if meta was found
        if ($metaCollectionFiltered->isEmpty()) {
            if ($create) {
                // create when not found
                $meta = new CollectionMeta();
                $meta->setLocale($this->locale);
                $meta->setCollection($this->entity);
                $this->entity->addMeta($meta);

                return $meta;
            }

            // return first when create false
            return $this->entity->getDefaultMeta();
        }

        // return exists
        return $metaCollectionFiltered->first();
    }
}
