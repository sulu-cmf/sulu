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

use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Webmozart\Assert\Assert;

class ContentMerger implements ContentMergerInterface
{
    /**
     * @var iterable<MergerInterface>
     */
    private $mergers;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param iterable<MergerInterface> $mergers
     */
    public function __construct(
        iterable $mergers,
        PropertyAccessor $propertyAccessor
    ) {
        $this->mergers = $mergers;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param DimensionContentCollectionInterface<T> $dimensionContentCollection
     *
     * @return T
     */
    public function merge(DimensionContentCollectionInterface $dimensionContentCollection): DimensionContentInterface
    {
        if (!$dimensionContentCollection->count()) {
            throw new \RuntimeException('Expected at least one dimensionContent given.');
        }

        $mergedDimensionContent = null;

        foreach ($dimensionContentCollection as $dimensionContent) {
            if (!$mergedDimensionContent) {
                $contentRichEntity = $dimensionContent->getResource();
                /** @var T $mergedDimensionContent */
                $mergedDimensionContent = $contentRichEntity->createDimensionContent();
                $mergedDimensionContent->markAsMerged();
            }

            foreach ($this->mergers as $merger) {
                $merger->merge($mergedDimensionContent, $dimensionContent);
            }

            foreach ($dimensionContentCollection->getDimensionAttributes() as $key => $value) {
                $this->propertyAccessor->setValue(
                    $mergedDimensionContent,
                    $key,
                    $this->propertyAccessor->getValue($dimensionContent, $key)
                );
            }
        }

        Assert::notNull($mergedDimensionContent);

        return $mergedDimensionContent;
    }
}
