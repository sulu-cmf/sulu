<?php

namespace Sulu\Bundle\LocationBundle\Tests\Functional\Controller;

use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class GeolocatorControllerTest extends SuluTestCase
{
    protected $mockPlugin;
    protected $client;

    public function setUp()
    {
        $this->client = $this->createAuthenticatedClient();

        $guzzleClient = $this->client->getContainer()->get('sulu_location.geolocator.guzzle.client');
        $this->mockPlugin = new MockPlugin();
        $guzzleClient->addSubscriber($this->mockPlugin);
    }

    public function testQuery()
    {
        $query = '10, Downing Street. London. England.';
        $rawResponse = file_get_contents(__DIR__ . '/responses/' . md5($query) . '.json');
        $this->mockPlugin->addResponse(new Response(200, null, $rawResponse));

        $router = $this->client->getContainer()->get('router');
        $this->client->request('get', $router->generate('sulu_location_geolocator_query', array(
            'providerName' => 'nominatim',
            'query' => $query,
        )));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
