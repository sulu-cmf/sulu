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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ResolverInterface
{
    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    public function resolve(DimensionContentInterface $dimensionContent): ContentView;
}
