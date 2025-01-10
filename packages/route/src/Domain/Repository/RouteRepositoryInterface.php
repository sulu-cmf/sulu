<?php

namespace Sulu\Route\Domain\Repository;

use Sulu\Route\Domain\Model\Route;

interface RouteRepositoryInterface
{
    public function add(Route $route): void;

    public function findOneBy(array $criteria): ?Route;
}
