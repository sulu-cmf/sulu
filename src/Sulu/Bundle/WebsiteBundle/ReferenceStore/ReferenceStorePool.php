<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

/**
 * Combines other reference-stores.
 */
class ReferenceStorePool implements ReferenceStorePoolInterface
{
    /**
     * @var array<string, ReferenceStoreInterface>
     */
    private $stores = [];

    /**
     * @param iterable<string, ReferenceStoreInterface> $stores
     */
    public function __construct(iterable $stores)
    {
        $this->stores = [...$stores];
    }

    public function getStores()
    {
        return $this->stores;
    }

    public function getStore($alias)
    {
        if (!\array_key_exists($alias, $this->stores)) {
            throw new ReferenceStoreNotExistsException($alias, \array_keys($this->stores));
        }

        return $this->stores[$alias];
    }
}
