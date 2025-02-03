<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Domain\Model;

use Sulu\Route\Domain\Model\Route;

trait RoutableTrait
{
    private ?Route $route;

    abstract public static function getResourceKey(): string;

    public function getResourceId(): int|string
    {
        return $this->getResource()->getId();
    }

    abstract public function getLocale(): ?string;

    abstract public function getResource(): ContentRichEntityInterface;

    public function setRoute(Route $route): void
    {
        $this->route = $route;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }
}
