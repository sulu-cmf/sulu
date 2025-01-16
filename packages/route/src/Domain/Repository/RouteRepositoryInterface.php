<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Domain\Repository;

use Sulu\Route\Domain\Model\Route;

/**
 * @phpstan-type RouteFilter array{
 *     site?: string|null,
 *     locale?: string,
 *     slug?: string,
 *     resourceKey?: string,
 *     resourceId?: string,
 * }
 */
interface RouteRepositoryInterface
{
    public function add(Route $route): void;

    /**
     * @param RouteFilter $filters
     */
    public function findOneBy(array $filters): ?Route;
}
