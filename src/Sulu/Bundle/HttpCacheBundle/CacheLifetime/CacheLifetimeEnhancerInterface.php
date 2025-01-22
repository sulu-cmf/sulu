<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\CacheLifetime;

use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The cache lifetime enhancer adds cache headers to the response.
 */
interface CacheLifetimeEnhancerInterface
{
    /**
     * Call this method to enhance the response.
     *
     * @return void
     */
    public function enhance(Response $response, StructureInterface $structure);
}
