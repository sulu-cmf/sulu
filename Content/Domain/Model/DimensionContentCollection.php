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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Sulu\Component\Util\SortUtils;

/**
 * @template-covariant T of DimensionContentInterface
 *
 * @implements \IteratorAggregate<T>
 * @implements DimensionContentCollectionInterface<T>
 */
class DimensionContentCollection implements \IteratorAggregate, DimensionContentCollectionInterface
{
    /**
     * @var ArrayCollection<int, T>
     */
    private $dimensionContents;

    /**
     * @var mixed[]
     */
    private $dimensionAttributes;

    /**
     * @var class-string<T>
     */
    private $dimensionContentClass;

    /**
     * @var mixed[]
     */
    private $defaultDimensionAttributes;

    /**
     * DimensionContentCollection constructor.
     *
     * @param T[] $dimensionContents
     * @param mixed[] $dimensionAttributes
     * @param class-string<T> $dimensionContentClass
     */
    public function __construct(
        array $dimensionContents,
        array $dimensionAttributes,
        string $dimensionContentClass
    ) {
        $this->dimensionContentClass = $dimensionContentClass;
        $this->defaultDimensionAttributes = $dimensionContentClass::getDefaultDimensionAttributes();
        $this->dimensionAttributes = $dimensionContentClass::getEffectiveDimensionAttributes($dimensionAttributes);

        $this->dimensionContents = new ArrayCollection(
            // dimension contents need to be sorted from most specific to least specific when they are merged
            SortUtils::multisort($dimensionContents, \array_keys($this->dimensionAttributes), 'asc')
        );
    }

    public function getDimensionContentClass(): string
    {
        return $this->dimensionContentClass;
    }

    public function getDimensionContent(array $dimensionAttributes): ?DimensionContentInterface
    {
        $dimensionAttributes = \array_merge($this->defaultDimensionAttributes, $dimensionAttributes);

        $criteria = Criteria::create();
        foreach ($dimensionAttributes as $key => $value) {
            if (null === $value) {
                $expr = $criteria->expr()->isNull($key);
            } else {
                $expr = $criteria->expr()->eq($key, $value);
            }

            $criteria->andWhere($expr);
        }

        return $this->dimensionContents->matching($criteria)->first() ?: null;
    }

    public function getDimensionAttributes(): array
    {
        return $this->dimensionAttributes;
    }

    /**
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        return $this->dimensionContents;
    }

    public function count(): int
    {
        return \count($this->dimensionContents);
    }
}
