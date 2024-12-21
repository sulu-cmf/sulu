<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Controller;

use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Handles snippet types and defaults.
 */
class SnippetAreaControllerTest extends BaseFunctionalTestCase
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();
    }

    public function testCGet(): void
    {
        $this->client->jsonRequest('GET', '/api/snippet-areas?webspace=sulu_io');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $data = $response['_embedded']['areas'];
        $this->assertEquals(3, $response['total']);
        $this->assertEquals('car', $data[0]['template']);
        $this->assertEquals('Car', $data[0]['title']);
        $this->assertEquals(null, $data[0]['defaultTitle']);
        $this->assertEquals(null, $data[0]['defaultUuid']);
        $this->assertEquals('hotel', $data[1]['template']);
        $this->assertEquals('Golf hotel', $data[1]['title']);
        $this->assertEquals(null, $data[1]['defaultTitle']);
        $this->assertEquals(null, $data[1]['defaultUuid']);
        $this->assertEquals('hotel', $data[2]['template']);
        $this->assertEquals('Sport hotel', $data[2]['title']);
        $this->assertEquals(null, $data[2]['defaultTitle']);
        $this->assertEquals(null, $data[2]['defaultUuid']);
    }

    public function testPutDefault(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/snippet-areas/car',
            ['webspace' => 'sulu_io', 'defaultUuid' => $this->car1->getUuid()]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('car', $response['template']);
        $this->assertEquals('Car', $response['title']);
        $this->assertEquals($this->car1->getUuid(), $response['defaultUuid']);
        $this->assertEquals($this->car1->getTitle(), $response['defaultTitle']);

        $this->client->jsonRequest('GET', '/api/snippet-areas?webspace=sulu_io');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $data = $response['_embedded']['areas'];

        $this->assertEquals(3, $response['total']);
        $this->assertEquals('car', $data[0]['template']);
        $this->assertEquals('Car', $data[0]['title']);
        $this->assertEquals($this->car1->getTitle(), $data[0]['defaultTitle']);
        $this->assertEquals($this->car1->getUuid(), $data[0]['defaultUuid']);
        $this->assertEquals('hotel', $data[1]['template']);
        $this->assertEquals('Golf hotel', $data[1]['title']);
        $this->assertEquals(null, $data[1]['defaultTitle']);
        $this->assertEquals(null, $data[1]['defaultUuid']);
        $this->assertEquals('hotel', $data[2]['template']);
        $this->assertEquals('Sport hotel', $data[2]['title']);
        $this->assertEquals(null, $data[2]['defaultTitle']);
        $this->assertEquals(null, $data[2]['defaultUuid']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutDefault')]
    public function testDeleteDefault(): void
    {
        $this->client->jsonRequest(
            'DELETE',
            '/api/snippet-areas/car',
            ['webspace' => 'sulu_io']
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('car', $response['template']);
        $this->assertEquals('Car', $response['title']);
        $this->assertEquals(null, $response['defaultUuid']);
        $this->assertEquals(null, $response['defaultTitle']);

        $this->client->jsonRequest('GET', '/api/snippet-areas?webspace=sulu_io');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $data = $response['_embedded']['areas'];

        $this->assertEquals(3, $response['total']);
        $this->assertEquals('car', $data[0]['template']);
        $this->assertEquals('Car', $data[0]['title']);
        $this->assertEquals(null, $data[0]['defaultTitle']);
        $this->assertEquals(null, $data[0]['defaultUuid']);
        $this->assertEquals('hotel', $data[1]['template']);
        $this->assertEquals('Golf hotel', $data[1]['title']);
        $this->assertEquals(null, $data[1]['defaultTitle']);
        $this->assertEquals(null, $data[1]['defaultUuid']);
        $this->assertEquals('hotel', $data[2]['template']);
        $this->assertEquals('Sport hotel', $data[2]['title']);
        $this->assertEquals(null, $data[2]['defaultTitle']);
        $this->assertEquals(null, $data[2]['defaultUuid']);
    }
}
