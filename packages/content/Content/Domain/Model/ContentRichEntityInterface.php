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

use Doctrine\Common\Collections\Collection;

/**
 * @template T of DimensionContentInterface
 */
interface ContentRichEntityInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @return Collection<int, T>
     */
    public function getDimensionContents(): Collection;

    /**
     * @return T
     */
    public function createDimensionContent(): DimensionContentInterface;

    /**
     * @param T $dimensionContent
     */
    public function addDimensionContent(DimensionContentInterface $dimensionContent): void;

    /**
     * @param T $dimensionContent
     */
    public function removeDimensionContent(DimensionContentInterface $dimensionContent): void;
}
