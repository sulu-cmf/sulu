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
use Sulu\Bundle\LocationBundle\Geolocator\Service\GoogleGeolocator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GoogleGeolocatorTest extends TestCase
{
    /**
     * @return array<array{string, int, array<string, string|null>}>
     */
    public static function provideLocate(): array
    {
        return [
            [
                '10, Rue Alexandre Dumas, Paris',
                1,
                [
                    'displayTitle' => '10 Rue Alexandre Dumas, 75011 Paris, France',
                    'street' => 'Rue Alexandre Dumas',
                    'number' => '10',
                    'code' => '75011',
                    'town' => 'Paris',
                    'country' => 'FR',
                    'longitude' => '2.3897064000000001',
                    'latitude' => '48.852964900000003',
                ],
            ],
            [
                'Dornbirn',
                1,
                [
                    'displayTitle' => 'Dornbirn, Austria',
                    'street' => null,
                    'number' => null,
                    'code' => null,
                    'town' => 'Dornbirn',
                    'country' => 'AT',
                    'longitude' => '9.7437899999999988',
                    'latitude' => '47.412399999999998',
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideLocate')]
    public function testLocate(string $query, int $expectedCount, $expectationMap): void
    {
        $fixtureName = __DIR__ . '/google-responses/' . \md5($query) . '.json';
        /** @var string $fixture */
        $fixture = \file_get_contents($fixtureName);
        $mockResponse = new MockResponse($fixture);

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new GoogleGeolocator($httpClient, '');

        $results = $geolocator->locate($query);
        $this->assertCount($expectedCount, $results);

        if (0 == \count($results)) {
            return;
        }

        $result = \current($results->toArray());

        foreach ($expectationMap as $field => $expectation) {
            $this->assertEquals($expectation, $result[$field]);
        }
    }

    public function testApiKey(): void
    {
        $mockResponse = new MockResponse('{"status": "OK","results":[]}');

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new GoogleGeolocator($httpClient, 'foobar-key');
        $geolocator->locate('foobar');

        $this->assertArrayHasKey('key', $mockResponse->getRequestOptions()['query']);
        $this->assertEquals('foobar-key', $mockResponse->getRequestOptions()['query']['key']);
    }

    public function testAcceptLanguage(): void
    {
        $mockResponse = new MockResponse('{"status": "OK","results":[]}');

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new GoogleGeolocator($httpClient, 'foobar-key');
        $options = new GeolocatorOptions();
        $options->setAcceptLanguage('it-IT, it;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5');
        $geolocator->locate('foobar', $options);

        $this->assertContains(
            'Accept-Language: it-IT, it;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5',
            $mockResponse->getRequestOptions()['headers'],
        );
    }
}
