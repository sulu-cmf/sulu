<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use ONGR\ElasticsearchBundle\Annotation\Property;
use ONGR\ElasticsearchBundle\Collection\Collection;

/**
 * Contains excerpt information for articles.
 *
 * @ObjectType
 */
class ExcerptViewObject
{
    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *        "fields"={
     *            "raw"={"type"="keyword"},
     *            "value"={"type"="text"}
     *        }
     *    }
     * )
     */
    public $title;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *        "fields"={
     *            "raw"={"type"="keyword"},
     *            "value"={"type"="text"}
     *        }
     *    }
     * )
     */
    public $more;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *        "fields"={
     *            "raw"={"type"="keyword"},
     *            "value"={"type"="text"}
     *        }
     *    }
     * )
     */
    public $description;

    /**
     * @var CategoryViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\CategoryViewObject", multiple=true)
     */
    public $categories;

    /**
     * @var TagViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\TagViewObject", multiple=true)
     */
    public $tags;

    /**
     * @var SegmentViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\SegmentViewObject", multiple=true)
     */
    public $segments;

    /**
     * @var int[]
     *
     * @Property(type="integer")
     */
    public $audienceTargetingGroups;

    /**
     * @var MediaViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\MediaViewObject", multiple=true)
     */
    public $icon;

    /**
     * @var MediaViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\MediaViewObject", multiple=true)
     */
    public $images;

    public function __construct()
    {
        $this->tags = new Collection();
        $this->categories = new Collection();
        $this->segments = new Collection();
        $this->icon = new Collection();
        $this->images = new Collection();
    }
}
