<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Manager;

use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\RouteIsNotUniqueException;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Manages routes.
 */
class RouteManager implements RouteManagerInterface
{
    public function __construct(
        private ChainRouteGeneratorInterface $chainRouteGenerator,
        private ConflictResolverInterface $conflictResolver,
        private RouteRepositoryInterface $routeRepository,
    ) {
    }

    public function create(RoutableInterface $entity, $path = null, $resolveConflict = true)
    {
        if (null !== $entity->getRoute()) {
            throw new RouteAlreadyCreatedException($entity);
        }

        $route = $this->chainRouteGenerator->generate($entity, $path);
        if ($resolveConflict) {
            $route = $this->conflictResolver->resolve($route);
        } elseif (!$this->isUnique($route)) {
            throw new RouteIsNotUniqueException($route, $entity);
        }

        $entity->setRoute($route);

        return $route;
    }

    public function update(RoutableInterface $entity, $path = null, $resolveConflict = true)
    {
        if (null === $entity->getRoute()) {
            throw new RouteNotCreatedException($entity);
        }

        $route = $this->chainRouteGenerator->generate($entity, $path);
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        if ($resolveConflict) {
            $route = $this->conflictResolver->resolve($route);
        } else {
            $route = $this->resolve($route, $entity);
        }

        // path haven't changed after conflict resolving
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $route = $this->handleHistoryRoutes($entity->getRoute(), $route);

        $entity->setRoute($route);

        return $route;
    }

    public function createOrUpdateByAttributes(
        string $entityClass,
        string $id,
        string $locale,
        string $path,
        $resolveConflict = true
    ): RouteInterface {
        $oldRoute = $this->routeRepository->findByEntity($entityClass, $id, $locale);

        if ($oldRoute && $oldRoute->getPath() === $path) {
            return $oldRoute;
        }

        $route = $this->createRoute($entityClass, $id, $locale, $path);

        if ($resolveConflict) {
            $route = $this->conflictResolver->resolve($route);
        }

        if ($oldRoute) {
            $route = $this->handleHistoryRoutes($oldRoute, $route);
        }

        $this->routeRepository->persist($route);

        return $route;
    }

    protected function handleHistoryRoutes(RouteInterface $oldRoute, RouteInterface $newRoute): RouteInterface
    {
        $historyRoute = $oldRoute->setHistory(true)->setTarget($newRoute);
        $newRoute->addHistory($historyRoute);

        foreach ($historyRoute->getHistories() as $historyRoute) {
            if ($historyRoute->getPath() === $newRoute->getPath()) {
                // the history route will be restored
                $historyRoute->removeTarget()->setHistory(false);

                continue;
            }

            $newRoute->addHistory($historyRoute);
            $historyRoute->setTarget($newRoute);
        }

        return $newRoute;
    }

    /**
     * Returns true if route is unique.
     *
     * @return bool
     */
    private function isUnique(RouteInterface $route)
    {
        $persistedRoute = $this->routeRepository->findByPath($route->getPath(), $route->getLocale());

        return !$persistedRoute;
    }

    /**
     * Looks for the same route in the database.
     * If no route was found the method returns the newly created route.
     * If the route is a history route for given entity the history route will be returned.
     * Else a RouteIsNotUniqueException will be thrown.
     *
     * @return RouteInterface
     *
     * @throws RouteIsNotUniqueException
     */
    private function resolve(RouteInterface $route, RoutableInterface $entity)
    {
        $persistedRoute = $this->routeRepository->findByPath($route->getPath(), $route->getLocale());

        if (!$persistedRoute) {
            return $route;
        }

        if ($persistedRoute->getEntityClass() === $route->getEntityClass()
            && $persistedRoute->getEntityId() === $route->getEntityId()
        ) {
            return $persistedRoute;
        }

        throw new RouteIsNotUniqueException($route, $entity);
    }

    private function createRoute(string $entityClass, string $id, string $locale, string $path): RouteInterface
    {
        return $this->routeRepository->createNew()
            ->setEntityClass($entityClass)
            ->setEntityId($id)
            ->setLocale($locale)
            ->setPath($path);
    }
}
