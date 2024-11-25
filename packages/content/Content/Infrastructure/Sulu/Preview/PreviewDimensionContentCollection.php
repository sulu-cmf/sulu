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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Preview;

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

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
     * @var T
     */
    private $previewDimensionContent;

    /**
     * @var string
     */
    private $previewLocale;

    /**
     * @param T $previewDimensionContent
     */
    public function __construct(
        DimensionContentInterface $previewDimensionContent,
        string $previewLocale
    ) {
        $this->previewDimensionContent = $previewDimensionContent;
        $this->previewLocale = $previewLocale;
    }

    public function getDimensionContentClass(): string
    {
        return \get_class($this->previewDimensionContent);
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
