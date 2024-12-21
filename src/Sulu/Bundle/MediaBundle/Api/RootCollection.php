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
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * The Collection Root RestObject is the api entity for the CollectionController.
 */
#[ExclusionPolicy('all')]
class RootCollection
{
    /**
     * @var string
     */
    #[Expose]
    private $id = 'root';

    /**
     * @var string
     */
    #[Expose]
    private $title;

    /**
     * @var bool
     */
    #[Expose]
    private $hasSub = true;

    /**
     * @var Collection[]
     */
    private $collections;

    public function __construct(string $title, array $collections)
    {
        $this->title = $title;
        $this->collections = $collections;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function hasSub()
    {
        return $this->hasSub;
    }

    /**
     * @return Collection[]
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * @internal
     */
    #[VirtualProperty]
    #[SerializedName('_embedded')]
    public function getEmbedded(): array
    {
        return [
            'collections' => $this->collections,
        ];
    }
}
