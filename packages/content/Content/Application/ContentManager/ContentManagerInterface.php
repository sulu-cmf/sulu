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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentManager;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

interface ContentManagerInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function resolve(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface;

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $data
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function persist(ContentRichEntityInterface $contentRichEntity, array $data, array $dimensionAttributes): DimensionContentInterface;

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return mixed[]
     */
    public function normalize(DimensionContentInterface $dimensionContent): array;

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $sourceContentRichEntity
     * @param mixed[] $sourceDimensionAttributes
     * @param ContentRichEntityInterface<T> $targetContentRichEntity
     * @param mixed[] $targetDimensionAttributes
     *
     * @return T
     */
    public function copy(
        ContentRichEntityInterface $sourceContentRichEntity,
        array $sourceDimensionAttributes,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes
    ): DimensionContentInterface;

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function applyTransition(
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes,
        string $transitionName
    ): DimensionContentInterface;

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
     * @param int|string $id
     * @param mixed[] $dimensionAttributes
     */
    public function deindex(string $resourceKey, $id, array $dimensionAttributes = []): void;
}
