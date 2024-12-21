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

/**
 * CollectionType.
 */
class CollectionType
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var DoctrineCollection<int, CollectionInterface>
     */
    #[Exclude]
    private $collections;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->collections = new ArrayCollection();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * To force id = 1 in load fixtures.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Add collections.
     *
     * @return $this
     */
    public function addCollection(CollectionInterface $collections)
    {
        $this->collections->add($collections);

        return $this;
    }

    /**
     * Remove collections.
     *
     * @return $this
     */
    public function removeCollection(CollectionInterface $collections)
    {
        $this->collections->removeElement($collections);

        return $this;
    }

    /**
     * Get collections.
     *
     * @return DoctrineCollection<int, CollectionInterface>
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * Set key.
     *
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key.
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }
}
