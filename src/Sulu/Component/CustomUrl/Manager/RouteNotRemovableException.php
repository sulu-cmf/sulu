<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when a current route will be deleted.
 */
class RouteNotRemovableException extends RestException
{
    public function __construct(
        private string $route,
        private CustomUrl $customUrl,
    ) {
        parent::__construct(
            \sprintf('Cannot delete current route "%s" of custom-url "%s"', $route, $customUrl->getTitle()),
            9000
        );
    }

    public function getCustomUrl(): CustomUrl
    {
        return $this->customUrl;
    }

    public function getRoute(): string
    {
        return $this->route;
    }
}
