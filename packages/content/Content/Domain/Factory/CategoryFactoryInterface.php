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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Factory;

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;

interface CategoryFactoryInterface
{
    /**
     * @param int[] $categoryIds
     *
     * @return CategoryInterface[]
     */
    public function create(array $categoryIds): array;
}
