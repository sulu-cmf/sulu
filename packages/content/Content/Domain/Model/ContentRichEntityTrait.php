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
use Doctrine\Common\Collections\Collection;

/**
 * @template T of DimensionContentInterface
 */
trait ContentRichEntityTrait
{
    /**
     * @var ArrayCollection<int, T>
     */
    protected $dimensionContents;

    /**
     * @return Collection<int, T>
     */
    public function getDimensionContents(): Collection
    {
        $this->initializeDimensionContents();

        return $this->dimensionContents;
    }

    /**
     * @param T $dimensionContent
     */
    public function addDimensionContent(DimensionContentInterface $dimensionContent): void
    {
        $this->initializeDimensionContents();

        $this->dimensionContents->add($dimensionContent);
    }

    /**
     * @param T $dimensionContent
     */
    public function removeDimensionContent(DimensionContentInterface $dimensionContent): void
    {
        $this->initializeDimensionContents();

        $this->dimensionContents->removeElement($dimensionContent);
    }

    private function initializeDimensionContents(): void
    {
        if (null === $this->dimensionContents) {
            $this->dimensionContents = new ArrayCollection();
        }
    }
}
