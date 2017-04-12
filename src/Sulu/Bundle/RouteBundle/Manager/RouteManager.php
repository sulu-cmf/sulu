<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Manager;

use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;

/**
 * Manages routes.
 */
class RouteManager implements RouteManagerInterface
{
    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @param ChainRouteGeneratorInterface $chainRouteGenerator
     * @param ConflictResolverInterface $conflictResolver
     */
    public function __construct(
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver
    ) {
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->conflictResolver = $conflictResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function create(RoutableInterface $entity, $path = null)
    {
        if (null !== $entity->getRoute()) {
            throw new RouteAlreadyCreatedException($entity);
        }

        $route = $this->chainRouteGenerator->generate($entity, $path);
        $route = $this->conflictResolver->resolve($route);
        $entity->setRoute($route);

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function update(RoutableInterface $entity, $path = null)
    {
        if (null === $entity->getRoute()) {
            throw new RouteNotCreatedException($entity);
        }

        $route = $this->chainRouteGenerator->generate($entity, $path);
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $route = $this->conflictResolver->resolve($route);

        // path haven't changed after conflict resolving
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $historyRoute = $entity->getRoute()
            ->setHistory(true)
            ->setTarget($route);
        $route->addHistory($historyRoute);

        foreach ($historyRoute->getHistories() as $historyRoute) {
            if ($historyRoute->getPath() === $route->getPath()) {
                // the history route will be restored
                $historyRoute->removeTarget()
                    ->setHistory(false);

                continue;
            }

            $route->addHistory($historyRoute);
            $historyRoute->setTarget($route);
        }

        $entity->setRoute($route);

        return $route;
    }
}
