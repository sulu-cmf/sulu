<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Geolocator;

class GeolocatorOptions
{
    /**
     * A string that represents the natural language and locale that the client prefers.
     * Should be formatted with the syntax used for the Accept-Language HTTP header.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language
     */
    private ?string $acceptLanguage = null;

    public function getAcceptLanguage(): ?string
    {
        return $this->acceptLanguage;
    }

    public function setAcceptLanguage(?string $acceptLanguage): self
    {
        $this->acceptLanguage = $acceptLanguage;

        return $this;
    }
}
