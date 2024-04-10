<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Filter;

class FilterTypeRegistry
{
    /**
     * @var FilterTypeInterface[]
     */
    private $filterTypes;

    /**
     * @param \Traversable<FilterTypeInterface> $filterTypes
     */
    public function __construct(\Traversable $filterTypes)
    {
        $this->filterTypes = \iterator_to_array($filterTypes);
    }

    public function getFilterType(string $type): FilterTypeInterface
    {
        if (!\array_key_exists($type, $this->filterTypes)) {
            throw new FilterTypeNotFoundException($type);
        }

        return $this->filterTypes[$type];
    }
}
