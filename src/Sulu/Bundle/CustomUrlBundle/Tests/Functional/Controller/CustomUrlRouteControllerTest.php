<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Functional\Controller;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CustomUrlRouteControllerTest extends SuluTestCase
{
    /**
     * @var PageDocument
     */
    private $contentDocument;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->initPhpcr();
        $this->contentDocument = $this->getContainer()->get('sulu_document_manager.document_manager')
            ->find('/cmf/sulu_io/contents', 'en');
    }

    public static function cdeleteRoutesProvider()
    {
        return [
            [
                [
                    [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-2', 'test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                [],
                ['test-1.sulu.io/test-1'],
            ],
            [
                [
                    [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-2', 'test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                ['test-1.sulu.io/test-1'],
                [],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cdeleteRoutesProvider')]
    public function testCDeleteRoutes(
        array $before,
        $data,
        $url,
        $delete,
        $excpected,
        $statusCode = 204,
        $restErrorCode = null
    ): void {
        foreach ($before as $beforeData) {
            $beforeData['targetDocument'] = $this->contentDocument->getUuid();
            $this->client->jsonRequest('POST', '/api/webspaces/sulu_io/custom-urls', $beforeData);
        }

        $data['targetDocument'] = $this->contentDocument->getUuid();

        $response = $this->client->getResponse();
        $responseData = \json_decode($response->getContent(), true);
        $uuid = $responseData['id'];

        $this->client->jsonRequest('PUT', '/api/webspaces/sulu_io/custom-urls/' . $uuid, $data);

        $response = $this->client->getResponse();
        $responseData = \json_decode($response->getContent(), true);
        $uuid = $responseData['id'];

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '/routes');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $customUrlRoutes = $response['_embedded']['custom_url_routes'];

        $uuids = [];
        foreach ($delete as $item) {
            $customUrlRoute = \array_filter($customUrlRoutes, function($customUrlRoute) use ($item) {
                return $customUrlRoute['resourcelocator'] === $item;
            });

            if (\count($customUrlRoute) > 0) {
                $uuids[] = $customUrlRoute[0]['id'];
            }
        }

        $this->client->jsonRequest(
            'DELETE',
            '/api/webspaces/sulu_io/custom-urls/' . $uuid . '/routes?ids=' . \implode(',', $uuids)
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode($statusCode, $response);

        if ($restErrorCode) {
            $responseData = \json_decode($response->getContent(), true);
            $this->assertEquals($restErrorCode, $responseData['code']);

            return;
        }

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid);
        $customUrl = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($url, $customUrl['customUrl']);

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '/routes');
        $customUrlRoutes = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(
            $excpected,
            \array_map(function($route) {
                return $route['resourcelocator'];
            }, $customUrlRoutes['_embedded']['custom_url_routes'])
        );
    }
}
