<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

/**
 * An instance of this exception signals that no route with given name was found.
 */
class ParentRouteNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $route;

    /**
     * @var string
     */
    private $parentRoute;

    public function __construct(string $parentRoute, string $route)
    {
        parent::__construct(
            sprintf(
                'The route "%s" was defined as the parent of "%s", but the route "%s" does not exist',
                $parentRoute,
                $route,
                $parentRoute
            )
        );

        $this->route = $route;
        $this->parentRoute = $parentRoute;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getParentRoute(): string
    {
        return $this->parentRoute;
    }
}
