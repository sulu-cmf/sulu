<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * File.
 */
class File implements AuditableInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    private $version;

    /**
     * @var int
     */
    private $id;

    /**
     * @var DoctrineCollection
     */
    private $fileVersions;

    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->fileVersions = new ArrayCollection();
    }

    /**
     * Set version.
     *
     * @param int $version
     *
     * @return File
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
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
     * Add fileVersions.
     *
     * @param FileVersion $fileVersions
     *
     * @return File
     */
    public function addFileVersion(FileVersion $fileVersions)
    {
        $this->fileVersions[] = $fileVersions;

        return $this;
    }

    /**
     * Remove fileVersions.
     *
     * @param FileVersion $fileVersions
     */
    public function removeFileVersion(FileVersion $fileVersions)
    {
        $this->fileVersions->removeElement($fileVersions);
    }

    /**
     * Get fileVersions.
     *
     * @return DoctrineCollection|FileVersion[]
     */
    public function getFileVersions()
    {
        return $this->fileVersions;
    }

    /**
     * Get file version.
     *
     * @param int $version
     *
     * @return FileVersion|null
     */
    public function getFileVersion($version)
    {
        /** @var FileVersion $fileVersion */
        foreach ($this->fileVersions as $fileVersion) {
            if ($fileVersion->getVersion() === $version) {
                return $fileVersion;
            }
        }

        return null;
    }

    /**
     * Get latest file version.
     *
     * @return FileVersion
     */
    public function getLatestFileVersion()
    {
        return $this->getFileVersion($this->version);
    }

    /**
     * Set media.
     *
     * @param MediaInterface $media
     *
     * @return File
     */
    public function setMedia(MediaInterface $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media.
     *
     * @return MediaInterface
     */
    public function getMedia()
    {
        return $this->media;
    }
}
