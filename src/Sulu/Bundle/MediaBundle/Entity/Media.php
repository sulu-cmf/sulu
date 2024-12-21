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
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Media.
 */
class Media implements MediaInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var DoctrineCollection<int, File>
     */
    protected $files;

    /**
     * @var CollectionInterface
     */
    #[Exclude]
    protected $collection;

    /**
     * @var MediaType
     */
    protected $type;

    /**
     * @var MediaInterface|null
     */
    protected $previewImage;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addFile(File $files)
    {
        $this->files[] = $files;

        return $this;
    }

    public function removeFile(File $files)
    {
        $this->files->removeElement($files);
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function setCollection(CollectionInterface $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setType(MediaType $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setPreviewImage(?MediaInterface $previewImage = null)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    public function getPreviewImage()
    {
        return $this->previewImage;
    }
}
