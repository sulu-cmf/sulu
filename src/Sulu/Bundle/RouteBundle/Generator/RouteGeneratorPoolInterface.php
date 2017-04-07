<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;

/**
 * Interface for route-generation pool.
 */
interface RouteGeneratorPoolInterface
{
    /**
     * Using configuration for entity to generate a route.
     *
     * @param RoutableInterface $entity
     * @param string $path
     *
     * @return GeneratedRoute
     */
    public function generate(RoutableInterface $entity, $path = null);
}
