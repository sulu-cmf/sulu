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
 * Geolocator which uses the google geocoding API.
 *
 * https://developers.google.com/maps/documentation/geocoding
 */
class GoogleGeolocator implements GeolocatorInterface
{
    public const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct(
        protected HttpClientInterface $client,
        protected string $apiKey
    ) {
    }

    public function locate(string $query, ?GeolocatorOptions $options = null): GeolocatorResponse
    {
        $requestHeaders = [];

        if ($options && $options->getAcceptLanguage()) {
            $requestHeaders['Accept-Language'] = $options->getAcceptLanguage();
        }

        $response = $this->client->request(
            'GET',
            self::ENDPOINT,
            [
                'headers' => $requestHeaders,
                'query' => [
                    'key' => $this->apiKey,
                    'address' => $query,
                ],
            ]
        );

        if (200 !== $response->getStatusCode()) {
            throw new HttpException(
                $response->getStatusCode(),
                \sprintf(
                    'Server at "%s" returned HTTP "%s". Body: ',
                    self::ENDPOINT,
                    $response->getStatusCode()
                )
            );
        }

        $googleResponse = $response->toArray();

        $response = new GeolocatorResponse();
        if ('OK' != $googleResponse['status']) {
            return $response;
        }

        $results = $googleResponse['results'];
        foreach ($results as $result) {
            $location = new GeolocatorLocation();

            $map = [];
            foreach ($result['address_components'] as $component) {
                foreach ($component['types'] as $type) {
                    if (isset($map[$type])) {
                        $map[$type][] = $component;
                    } else {
                        $map[$type] = [$component];
                    }
                }
            }

            // google provides no ID - so we just make one up ...
            $location->setId(\md5(\serialize($result)));
            $location->setDisplayTitle($result['formatted_address']);

            foreach ([
                'route' => ['method' => 'setStreet', 'field' => 'long_name'],
                'street_number' => ['method' => 'setNumber', 'field' => 'long_name'],
                'postal_code' => ['method' => 'setCode', 'field' => 'long_name'],
                'locality' => ['method' => 'setTown', 'field' => 'long_name'],
                'country' => ['method' => 'setCountry', 'field' => 'short_name'],
            ] as $field => $property) {
                if (isset($map[$field])) {
                    $parts = [];
                    foreach ($map[$field] as $fieldValue) {
                        $parts[] = $fieldValue[$property['field']];
                    }
                    $location->{$property['method']}(\implode(', ', $parts));
                }
            }

            $geometry = $result['geometry'];
            $location->setLongitude($geometry['location']['lng']);
            $location->setLatitude($geometry['location']['lat']);

            $response->addLocation($location);
        }

        return $response;
    }
}
