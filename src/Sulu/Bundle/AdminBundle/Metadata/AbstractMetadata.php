<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata;

use JMS\Serializer\Annotation as Serializer;

abstract class AbstractMetadata implements MetadataInterface
{
    /**
     * @var bool
     */
    #[Serializer\Exclude]
    protected $cacheable = true;

    public function isCacheable(): bool
    {
        return $this->cacheable;
    }

    public function setCacheable(bool $cacheable): void
    {
        $this->cacheable = $cacheable;
    }
}
