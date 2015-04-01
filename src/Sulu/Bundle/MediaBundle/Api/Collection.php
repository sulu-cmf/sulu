<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Api;

use Hateoas\Configuration\Annotation\Embedded;
use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Class Collection
 * The Collection RestObject is the api entity for the CollectionController.
 * @ExclusionPolicy("all")
 * FIXME Remove limit = 9999 after create cget without pagination
 * @Relation(
 *      "all",
 *      href = @Route(
 *          "cget_media",
 *          parameters = { "collection" = "expr(object.getId())", "limit" = 9999, "orderBy" = "media.changed", "orderSort" = "DESC" }
 *      )
 * )
 * @Relation(
 *      "filterByTypes",
 *      href = @Route(
 *          "cget_media",
 *          parameters = { "collection" = "expr(object.getId())", "limit" = 9999, "types" = "{types}", "orderBy" = "media.changed", "orderSort" = "DESC" }
 *      )
 * )
 * @Relation(
 *      "self",
 *      href = @Route(
 *          "get_collection",
 *          parameters = { "id" = "expr(object.getId())" }
 *      )
 * )
 * @Relation(
 *      "admin",
 *      href ="expr('media/collections/edit:'~object.getId()~'/files')"
 * )
 * @Relation(
 *      "children",
 *      href = @Route(
 *          "get_collection",
 *          parameters = { "id" = "expr(object.getId())", "depth" = 1 }
 *      )
 * )
 * @Relation(
 *     name = "collections",
 *     embedded = @Embedded(
 *         "expr(object.getChildren())",
 *         xmlElementName = "collections"
 *     )
 * )
 * @Relation(
 *     name = "parent",
 *     embedded = @Embedded(
 *         "expr(object.getParent())",
 *         xmlElementName = "parent"
 *     )
 * )
 * @Relation(
 *     name = "breadcrumb",
 *     embedded = @Embedded(
 *         "expr(object.getBreadcrumb())",
 *         xmlElementName = "breadcrumb"
 *     )
 * )
 */
class Collection extends ApiWrapper
{
    /**
     * @var array
     */
    protected $preview = array();

    /**
     * @var array
     */
    protected $properties = array();

    /**
     * @var Collection[]
     */
    protected $children = array();

    /**
     * @var Collection
     */
    protected $parent;

    /**
     * @var array
     */
    protected $breadcrumb;

    public function __construct(CollectionInterface $collection, $locale)
    {
        $this->entity = $collection;
        $this->locale = $locale;
    }

    /**
     * Set children of resource
     * @param Collection[] $children
     */
    public function setChildren($children)
    {
        $childrenEntities = array();

        foreach ($children as $child) {
            $childrenEntities[] = $child->getEntity();
        }

        // FIXME remove cache for children and generate then on the fly
        //       reason: preview images cannot be generated without a service
        $this->children = $children;
    }

    /**
     * Returns children of resource
     * @return Collection[]
     */
    public function getChildren()
    {
        // FIXME remove cache for children and generate then on the fly
        //       reason: preview images cannot be generated without a service
        return $this->children;
    }

    /**
     * Indicates if sub collections exists
     * @VirtualProperty
     * @SerializedName("hasSub")
     * @return boolean
     */
    public function getHasSub()
    {
        if (($children = $this->getEntity()->getChildren()) !== null) {
            return $children->count() > 0;
        }

        return false;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->getMeta(true)->setDescription($description);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("description")
     * @return string
     */
    public function getDescription()
    {
        $meta = $this->getMeta();
        if ($meta) {
            return $meta->getDescription();
        }

        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @param Collection $parent
     * @return $this
     */
    public function setParent($parent)
    {
        if ($parent !== null) {
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
     * @param array $style
     * @return $this
     */
    public function setStyle($style)
    {
        if (!is_string($style)) {
            $style = json_encode($style);
        }
        $this->entity->setStyle($style);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("style")
     * @return array
     */
    public function getStyle()
    {
        return json_decode($this->entity->getStyle(), true);
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("properties")
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @VirtualProperty
     * @SerializedName("preview")
     * @return array
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param array $preview
     * @return $this
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("locale")
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->getMeta(true)->setTitle($title);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("title")
     * @return string
     */
    public function getTitle()
    {
        $meta = $this->getMeta();
        if ($meta) {
            return $meta->getTitle();
        }

        return null;
    }

    /**
     * @param CollectionType $type
     * @return $this
     */
    public function setType($type)
    {
        $this->entity->setType($type);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("type")
     * @return CollectionType
     */
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     * @return string
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * @param UserInterface $changer
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->entity->setChanger($changer);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changer")
     * @return string
     */
    public function getChanger()
    {
        $user = $this->entity->getCreator();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     * @return string
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * @param UserInterface $creator
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->entity->setCreator($creator);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("creator")
     * @return string
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
     * @param bool $create
     * @return CollectionMeta
     */
    private function getMeta($create = false)
    {
        $locale = $this->locale;
        $metaCollection = $this->entity->getMeta();

        // get meta only with this locale
        $metaCollectionFiltered = $metaCollection->filter(
            function ($meta) use ($locale) {
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
            } elseif (!$metaCollection->isEmpty()) {
                // return first when create false
                return $metaCollection->first();
            }
        } else {
            // return exists
            return $metaCollectionFiltered->first();
        }

        return null;
    }
}
