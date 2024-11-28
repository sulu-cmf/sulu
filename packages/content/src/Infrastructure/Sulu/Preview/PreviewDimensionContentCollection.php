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

namespace Sulu\Content\Infrastructure\Sulu\Preview;

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal
 *
 * @template-covariant T of DimensionContentInterface
 *
 * @implements DimensionContentCollectionInterface<T>
 * @implements \IteratorAggregate<T>
 */
class PreviewDimensionContentCollection implements \IteratorAggregate, DimensionContentCollectionInterface
{
    /**
     * @param T $previewDimensionContent
     */
    public function __construct(
        private DimensionContentInterface $previewDimensionContent,
        private string $previewLocale,
    ) {
    }

    public function getDimensionContentClass(): string
    {
        return $this->previewDimensionContent::class;
    }

    public function getDimensionContent(array $dimensionAttributes): ?DimensionContentInterface
    {
        return $this->previewDimensionContent;
    }

    public function getDimensionAttributes(): array
    {
        return \array_merge(
            $this->previewDimensionContent::getDefaultDimensionAttributes(),
            ['locale' => $this->previewLocale]
        );
    }

    public function getIterator(): \Traversable
    {
        return new ArrayCollection([$this->previewDimensionContent]);
    }

    public function count(): int
    {
        return 1;
    }
}
