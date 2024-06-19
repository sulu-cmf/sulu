<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Controller;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GeolocatorController
{
    public function __construct(
        private GeolocatorInterface $geolocator
    ) {
    }

    /**
     * Query the configured geolocation service.
     */
    public function queryAction(Request $request): JsonResponse
    {
        $query = $request->get('search', '');

        $res = $this->geolocator->locate($query);

        return new JsonResponse(['_embedded' => ['geolocator_locations' => $res->toArray()]]);
    }
}
