<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\LocationBundle\Controller\GeolocatorController;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorOptions;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GeolocatorControllerTest extends TestCase
{
    use ProphecyTrait;

    public function testQuery(): void
    {
        $geolocatorResponse = $this->prophesize(GeolocatorResponse::class);
        $geolocatorResponse->toArray()->willReturn(['test']);

        $locator = $this->prophesize(GeolocatorInterface::class);
        $locator->locate('Dornbirn Teststraße 6', Argument::that(function(GeolocatorOptions $options) {
            $this->assertSame('de', $options->getAcceptLanguage());

            return true;
        }))
            ->willReturn($geolocatorResponse->reveal())
            ->shouldBeCalled();

        $controller = new GeolocatorController($locator->reveal());
        $request = Request::create('/test', 'GET', ['search' => 'Dornbirn Teststraße 6', 'locale' => 'de']);

        $response = $controller->queryAction($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            ['_embedded' => ['geolocator_locations' => ['test']]],
            \json_decode($response->getContent(), true)
        );
    }
}
