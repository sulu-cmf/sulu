<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentMerger;

use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentMergerInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param DimensionContentCollectionInterface<T> $dimensionContentCollection
     *
     * @return T
     */
    public function merge(DimensionContentCollectionInterface $dimensionContentCollection): DimensionContentInterface;
}
