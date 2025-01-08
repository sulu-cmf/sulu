<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Geolocator\Service;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorOptions;
use Sulu\Bundle\LocationBundle\Geolocator\Service\NominatimGeolocator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NominatimGeolocatorTest extends TestCase
{
    /**
     * @return array<array{string, int, array<string, string|null>}>
     */
    public static function provideLocate(): array
    {
        return [
            [
                '10, Rue Alexandre Dumas, Paris',
                2,
                [
                    'displayTitle' => '10, Rue Alexandre Dumas, Ste-Marguerite, 11th Arrondissement, Paris, Ile-de-France, F-75011, Metropolitan France, European Union',
                    'street' => 'Rue Alexandre Dumas',
                    'number' => '10',
                    'code' => 'F-75011',
                    'town' => 'Paris',
                    'country' => 'FR',
                    'longitude' => '2.3898894',
                    'latitude' => '48.8529486',
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideLocate')]
    public function testLocate(string $query, int $expectedCount, array $expectationMap): void
    {
        $fixtureName = __DIR__ . '/responses/' . \md5($query) . '.json';
        /** @var string $fixture */
        $fixture = \file_get_contents($fixtureName);
        $mockResponse = new MockResponse($fixture);

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new NominatimGeolocator($httpClient, 'https://example.org', 'key');

        $results = $geolocator->locate($query);
        $this->assertCount($expectedCount, $results);

        $result = \current($results->toArray());

        foreach ($expectationMap as $field => $expectation) {
            $this->assertEquals($expectation, $result[$field]);
        }
    }

    public function testAcceptLanguage(): void
    {
        $mockResponse = new MockResponse('[]');

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new NominatimGeolocator($httpClient, 'https://example.org', 'key');
        $options = new GeolocatorOptions();
        $options->setAcceptLanguage('it-IT, it;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5');
        $geolocator->locate('foobar', $options);

        $this->assertArrayHasKey('accept-language', $mockResponse->getRequestOptions()['query']);
        $this->assertEquals('it-IT, it;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5', $mockResponse->getRequestOptions()['query']['accept-language']);
    }
}
