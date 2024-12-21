<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * This route loader should only load the containing routes if the versioning feature in the document manager is enabled.
 */
class VersionRouteLoader extends Loader
{
    /**
     * @param bool $enabled
     */
    public function __construct(private $enabled)
    {
    }

    /**
     * @param string $resource
     */
    public function load($resource, $type = null): mixed
    {
        if (!$this->enabled) {
            return new RouteCollection();
        }

        return $this->import($resource, 'rest');
    }

    public function supports($resource, $type = null): bool
    {
        return 'versioning_rest' === $type;
    }
}
