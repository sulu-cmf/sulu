<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Event;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An object of this class is thrown along with the tag.merge event.
 */
class TagMergeEvent extends Event
{
    /**
     * The deleted Tag.
     *
     * @var array
     */
    protected $srcTags;

    /**
     * The Tag the deleted Tag got merged into.
     *
     * @var TagInterface
     */
    protected $destTag;

    /**
     * @param array $srcTags The deleted Tag
     * @param TagInterface $destTag The Tag the deleted Tag got merged into
     */
    public function __construct(array $srcTags, TagInterface $destTag)
    {
        $this->srcTags = $srcTags;
        $this->destTag = $destTag;
    }

    /**
     * Returns the Tag which got deleted.
     *
     * @return TagInterface
     */
    public function getSrcTags()
    {
        return $this->srcTags;
    }

    /**
     * Returns the Tag in which the deleted Tag got merged.
     *
     * @return TagInterface
     */
    public function getDestTag()
    {
        return $this->destTag;
    }
}
