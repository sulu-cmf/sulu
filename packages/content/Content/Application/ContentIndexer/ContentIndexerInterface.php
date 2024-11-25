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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentIndexer;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

interface ContentIndexerInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function index(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface;

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     */
    public function indexDimensionContent(DimensionContentInterface $dimensionContent): void;

    /**
     * @param int|string $id
     * @param mixed[] $dimensionAttributes
     */
    public function deindex(string $resourceKey, $id, array $dimensionAttributes = []): void;

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     */
    public function deindexDimensionContent(DimensionContentInterface $dimensionContent): void;
}
