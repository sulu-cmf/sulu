<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\SecurityBundle\Entity\PermissionInheritanceInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Collection.
 */
class Collection implements CollectionInterface, PermissionInheritanceInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $style;

    /**
     * @var int
     */
    #[Exclude]
    protected $lft;

    /**
     * @var int
     */
    #[Exclude]
    protected $rgt;

    /**
     * @var int
     */
    #[Exclude]
    protected $depth;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var CollectionType
     */
    protected $type;

    /**
     * @var UserInterface|null
     */
    #[Exclude]
    protected $changer;

    /**
     * @var UserInterface|null
     */
    #[Exclude]
    protected $creator;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @var DoctrineCollection<int, CollectionMeta>
     */
    private $meta;

    /**
     * @var DoctrineCollection<int, MediaInterface>
     */
    #[Exclude]
    private $media;

    /**
     * @var DoctrineCollection<int, CollectionInterface>
     */
    private $children;

    /**
     * @var CollectionInterface|null
     */
    private $parent;

    /**
     * @var CollectionMeta
     */
    private $defaultMeta;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set changer.
     *
     * @return CollectionInterface
     */
    public function setChanger(?UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface|null
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @return CollectionInterface
     */
    public function setCreator(?UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface|null
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set style.
     *
     * @param string|null $style
     *
     * @return CollectionInterface
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style.
     *
     * @return string|null
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CollectionInterface
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return CollectionInterface
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return CollectionInterface
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @return $this
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return $this
     */
    public function setChanged(\DateTime $changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Set type.
     *
     * @return CollectionInterface
     */
    public function setType(CollectionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return CollectionType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set key.
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get key.
     *
     * @param string|null $key
     *
     * @return CollectionInterface
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return DoctrineCollection<int, self>
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren(DoctrineCollection $children)
    {
        $this->children = $children;
    }

    /**
     * Set parent.
     *
     * @return CollectionInterface
     */
    public function setParent(?CollectionInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return CollectionInterface|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return int|null
     */
    public function getParentId()
    {
        if ($this->parent) {
            return $this->parent->getId();
        }

        return null;
    }

    /**
     * Add meta.
     *
     * @return Collection
     */
    public function addMeta(CollectionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @return void
     */
    public function removeMeta(CollectionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return DoctrineCollection<int, CollectionMeta>
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Add media.
     *
     * @return Collection
     */
    public function addMedia(MediaInterface $media)
    {
        $this->media[] = $media;

        return $this;
    }

    /**
     * Remove media.
     *
     * @return void
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media.
     *
     * @return DoctrineCollection<int, MediaInterface>
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Add children.
     *
     * @return Collection
     */
    public function addChildren(CollectionInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @return void
     */
    public function removeChildren(CollectionInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Set defaultMeta.
     *
     * @return Collection
     */
    public function setDefaultMeta(?CollectionMeta $defaultMeta = null)
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    /**
     * Get defaultMeta.
     *
     * @return CollectionMeta
     */
    public function getDefaultMeta()
    {
        return $this->defaultMeta;
    }

    public function getSecurityContext()
    {
        return 'sulu.media.collections';
    }
}
