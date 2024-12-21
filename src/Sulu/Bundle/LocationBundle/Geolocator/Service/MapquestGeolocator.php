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

namespace Sulu\Bundle\LocationBundle\Geolocator\Service;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorLocation;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorOptions;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

/**
 * https://developer.mapquest.com/documentation/.
 */
class MapquestGeolocator implements GeolocatorInterface
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    private $key;

    public function __construct(
        HttpClientInterface $client,
        string $baseUrl,
        string $key,
    ) {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->key = $key;
    }

    public function locate(string $query, ?GeolocatorOptions $options = null): GeolocatorResponse
    {
        $requestHeaders = [];

        if ($options && $options->getAcceptLanguage()) {
            $requestHeaders['Accept-Language'] = $options->getAcceptLanguage();
        }

        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'headers' => $requestHeaders,
                'query' => [
                    'location' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'key' => $this->key,
                ],
            ],
        );

        if (200 !== $response->getStatusCode()) {
            throw new HttpException(
                $response->getStatusCode(),
                \sprintf(
                    'Server at "%s" returned HTTP "%s". Body: ',
                    $this->baseUrl,
                    $response->getStatusCode(),
                ),
            );
        }

        $responseBody = $response->toArray();

        if (0 !== ($responseBody['info']['statuscode'] ?? 0)) { // mapquest does not return the correct status code instead a statuscode field
            throw new HttpException(
                $responseBody['info']['statuscode'],
                \sprintf(
                    'Server at "%s" returned HTTP "%s". Body: ',
                    $this->baseUrl,
                    $responseBody['info']['statuscode'],
                ),
            );
        }

        Assert::keyExists($responseBody, 'results');
        /** @var array<array{
         *   locations?: array<array{
         *       street?: string,
         *       postalCode?: string,
         *       adminArea5?: string,
         *       adminArea1?: string,
         *       latLng?: array{
         *           lat: float,
         *           lng: float,
         *       },
         *   }>
         * }> $results
         */
        $results = $responseBody['results'];
        $geolocatorResponse = new GeolocatorResponse();

        foreach ($results as $result) {
            $locations = $result['locations'] ?? [];

            foreach ($locations as $locationId => $location) {
                $geoLocation = new GeolocatorLocation();

                foreach ([
                    'setStreet' => 'street',
                    'setCode' => 'postalCode',
                    'setTown' => 'adminArea5',
                    'setCountry' => 'adminArea1',
                ] as $method => $key) {
                    if (isset($location[$key])) {
                        $geoLocation->$method($location[$key]);
                    }
                }

                if (isset($location['latLng'])) {
                    $geoLocation->setLatitude($location['latLng']['lat']);
                    $geoLocation->setLongitude($location['latLng']['lng']);
                }

                $geoLocation->setId((string) $locationId);
                $displayTitle = \array_filter([$geoLocation->getStreet(), $geoLocation->getTown(), $geoLocation->getCode(), $geoLocation->getCountry()]);
                $geoLocation->setDisplayTitle(\implode(', ', $displayTitle));

                $geolocatorResponse->addLocation($geoLocation);
            }
        }

        return $geolocatorResponse;
    }
}
