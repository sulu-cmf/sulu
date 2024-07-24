<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index\Factory;

use Sulu\Bundle\ArticleBundle\Document\ExcerptViewObject;

/**
 * Create a media excerpt view object.
 */
class ExcerptFactory
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var TagCollectionFactory
     */
    private $tagCollectionFactory;

    /**
     * @var MediaCollectionFactory
     */
    private $mediaCollectionFactory;

    /**
     * @var SegmentCollectionFactory
     */
    private $segmentCollectionFactory;

    /**
     * ExcerptIndexerFactory constructor.
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        TagCollectionFactory $tagCollectionFactory,
        MediaCollectionFactory $mediaCollectionFactory,
        SegmentCollectionFactory $segmentCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->mediaCollectionFactory = $mediaCollectionFactory;
        $this->segmentCollectionFactory = $segmentCollectionFactory;
    }

    /**
     * Create a excerpt object by given data.
     */
    public function create(array $data, string $locale): ExcerptViewObject
    {
        $excerpt = new ExcerptViewObject();

        if (empty($data)) {
            return $excerpt;
        }

        $excerpt->title = $data['title'];
        $excerpt->more = $data['more'];
        $excerpt->description = $data['description'];
        $excerpt->tags = $this->tagCollectionFactory->create($data['tags']);
        $excerpt->categories = $this->categoryCollectionFactory->create($data['categories'], $locale);
        $excerpt->segments = $this->segmentCollectionFactory->create($data['segments'] ?? []);
        $excerpt->audienceTargetingGroups = $data['audience_targeting_groups'] ?? [];
        $excerpt->icon = $this->mediaCollectionFactory->create($data['icon'] ?? [], $locale);
        $excerpt->images = $this->mediaCollectionFactory->create($data['images'] ?? [], $locale);

        return $excerpt;
    }
}
