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

use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Contains localization state information for articles.
 *
 * @ObjectType
 */
class LocalizationStateViewObject
{
    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $state;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $locale;

    public function __construct(?string $state = null, ?string $locale = null)
    {
        $this->state = $state;
        $this->locale = $locale;
    }
}
