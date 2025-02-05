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

namespace Sulu\Content\Application\ContentCopier;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentCopierInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $sourceContentRichEntity
     * @param mixed[] $sourceDimensionAttributes
     * @param ContentRichEntityInterface<T> $targetContentRichEntity
     * @param mixed[] $targetDimensionAttributes
     * @param array{data?: array<string, mixed>, ignoredAttributes?: string[]} $options the "data" allows given custom data to the target and "ignoredAttributes" avoids specific attributes to be copied
     *
     * @return T
     */
    public function copy(
        ContentRichEntityInterface $sourceContentRichEntity,
        array $sourceDimensionAttributes,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes,
        array $options = []
    ): DimensionContentInterface;

    /**
     * @template T of DimensionContentInterface
     *
     * @param DimensionContentCollectionInterface<T> $dimensionContentCollection
     * @param ContentRichEntityInterface<T> $targetContentRichEntity
     * @param mixed[] $targetDimensionAttributes
     * @param array{data?: array<string, mixed>, ignoredAttributes?: string[]} $options the "data" allows given custom data to the target and "ignoredAttributes" avoids specific attributes to be copied
     *
     * @return T
     */
    public function copyFromDimensionContentCollection(
        DimensionContentCollectionInterface $dimensionContentCollection,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes,
        array $options = []
    ): DimensionContentInterface;

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     * @param ContentRichEntityInterface<T> $targetContentRichEntity
     * @param mixed[] $targetDimensionAttributes
     * @param array{data?: array<string, mixed>, ignoredAttributes?: string[]} $options the "data" allows given custom data to the target and "ignoredAttributes" avoids specific attributes to be copied
     *
     * @return T
     */
    public function copyFromDimensionContent(
        DimensionContentInterface $dimensionContent,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes,
        array $options = []
    ): DimensionContentInterface;
}
