<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

/**
 * Memoizer which uses Doctrine Cache to save data.
 */
interface MemoizeInterface
{
    /**
     * Returns the value stored in the cache or uses the passed function to compute the value and save to cache
     * This function uses the given key for the caching mechanism.
     *
     * @param array $arguments array of parameter to call compute function
     * @param callable $compute
     * @param int $lifeTime cache lifetime
     */
    public function memoizeById($id, $arguments, $compute, $lifeTime = null);
}
