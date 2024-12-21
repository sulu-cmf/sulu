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
 * MediaType.
 */
class MediaType
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var int
     */
    private $id;

    /**
     * @var DoctrineCollection<int, MediaInterface>
     */
    #[Exclude]
    private $media;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->media = new ArrayCollection();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return MediaType
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
     * @return MediaType
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
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Add media.
     *
     * @return MediaType
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
}
