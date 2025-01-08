<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Geolocator\Service;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorLocation;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorOptions;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Geolocator which uses the open street maps nominatim service.
 *
 * http://wiki.openstreetmap.org/wiki/Nominatim
 */
class NominatimGeolocator implements GeolocatorInterface
{
    public function __construct(
        protected HttpClientInterface $client,
        protected string $baseUrl,
        private string $key
    ) {
    }

    public function locate(string $query, ?GeolocatorOptions $options = null): GeolocatorResponse
    {
        $requestQuery = [
            'q' => $query,
            'format' => 'json',
            'addressdetails' => 1,
            'key' => $this->key,
        ];

        if ($options && $options->getAcceptLanguage()) {
            $requestQuery['accept-language'] = $options->getAcceptLanguage();
        }

        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'query' => $requestQuery,
            ]
        );

        if (200 != $response->getStatusCode()) {
            throw new HttpException(
                $response->getStatusCode(),
                \sprintf(
                    'Server at "%s" returned HTTP "%s". Body: ',
                    $this->baseUrl,
                    $response->getStatusCode()
                )
            );
        }

        $geolocatorResponse = new GeolocatorResponse();
        foreach ($response->toArray() as $result) {
            $location = new GeolocatorLocation();

            foreach ([
                'setStreet' => 'road',
                'setNumber' => 'house_number',
                'setCode' => 'postcode',
                'setTown' => 'city',
                'setCountry' => 'country_code',
            ] as $method => $key) {
                if (isset($result['address'][$key])) {
                    $location->$method($result['address'][$key]);
                }
            }

            $location->setId($result['place_id']);
            $location->setLongitude($result['lon']);
            $location->setLatitude($result['lat']);
            $location->setDisplayTitle($result['display_name']);

            $geolocatorResponse->addLocation($location);
        }

        return $geolocatorResponse;
    }
}
