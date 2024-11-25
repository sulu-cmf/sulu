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

/**
 * @template-covariant T of DimensionContentInterface
 *
 * @extends \Traversable<T>
 */
interface DimensionContentCollectionInterface extends \Traversable, \Countable
{
    /**
     * @param mixed[] $dimensionAttributes
     *
     * @return T|null
     */
    public function getDimensionContent(array $dimensionAttributes): ?DimensionContentInterface;

    /**
     * @return class-string<T>
     */
    public function getDimensionContentClass(): string;

    /**
     * @return mixed[]
     */
    public function getDimensionAttributes(): array;
}
