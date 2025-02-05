<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Functional\Analyzer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\PortalInformationRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\UrlRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestAnalyzerTest extends TestCase
{
    use ProphecyTrait;

    private Webspace $webspace;
    private Portal $portal;
    private RequestAnalyzer $requestAnalyzer;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    public function setUp(): void
    {
        $this->webspace = new Webspace();
        $this->webspace->setKey('sulu');

        $this->portal = new Portal();
        $this->portal->setKey('sulu');

        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->requestAnalyzer = new RequestAnalyzer(
            $this->requestStack->reveal(),
            [
                new UrlRequestProcessor(),
                new WebsiteRequestProcessor(
                    $this->webspaceManager->reveal(), 'prod'
                ),
                new PortalInformationRequestProcessor(),
            ]
        );
    }

    protected function prepareWebspaceManager(array $config)
    {
        $portalInformation = new PortalInformation(
            type: $config['match_type'],
            webspace: $this->webspace,
            portal: $this->portal,
            localization: new Localization('de', 'at'),
            url: $config['portal_url'],
            redirect: $config['redirect']
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), Argument::any())
            ->willReturn([$portalInformation]);
        $this->webspaceManager->getPortalInformations(Argument::any())->willReturn([]);
    }

    public static function provideAnalyze()
    {
        return [
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                ],
                [
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                ],
            ],
        ];
    }

    public static function provideAnalyzeWithFormat()
    {
        return [
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to.html',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'format' => 'html',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to.rss',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                ],
                [
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                    'format' => 'rss',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'format' => 'html',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to/test.min.css',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to/test',
                    'portal_url' => 'sulu.lo/test',
                    'format' => 'css',
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAnalyze')]
    public function testAnalyze($config, $expected = []): void
    {
        $this->prepareWebspaceManager($config);

        $request = new Request(['get' => 1], ['post' => 1], [], [], [], ['REQUEST_URI' => $config['path_info']]);
        $request->headers->set('HOST', 'sulu.lo');

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de_at', $request->getLocale());
        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocale());
        $this->assertEquals('sulu', $this->requestAnalyzer->getWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getSegment());
        $this->assertEquals($expected['portal_url'], $this->requestAnalyzer->getPortalUrl());
        $this->assertEquals($expected['redirect'], $this->requestAnalyzer->getRedirect());
        $this->assertEquals($expected['resource_locator'], $this->requestAnalyzer->getResourceLocator());
        $this->assertEquals($expected['resource_locator_prefix'], $this->requestAnalyzer->getResourceLocatorPrefix());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAnalyzeWithFormat')]
    public function testAnalyzeWithFormat($config, $expected = []): void
    {
        $this->prepareWebspaceManager($config);

        $request = new Request(['get' => 1], ['post' => 1], [], [], [], ['REQUEST_URI' => $config['path_info']]);
        $request->headers->set('HOST', 'sulu.lo');

        $this->requestStack->getCurrentRequest()->willReturn($request);
        $this->requestAnalyzer->analyze($request);

        $this->assertEquals($expected['format'], $request->getRequestFormat());
        $this->assertEquals('de_at', $request->getLocale());
        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocale());
        $this->assertEquals('sulu', $this->requestAnalyzer->getWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getSegment());
        $this->assertEquals($expected['portal_url'], $this->requestAnalyzer->getPortalUrl());
        $this->assertEquals($expected['redirect'], $this->requestAnalyzer->getRedirect());
        $this->assertEquals($expected['resource_locator'], $this->requestAnalyzer->getResourceLocator());
        $this->assertEquals(
            $expected['resource_locator_prefix'],
            $this->requestAnalyzer->getResourceLocatorPrefix()
        );
        $this->assertEquals($expected['format'], $request->getRequestFormat());
    }

    public function testAnalyzeNotExisting(): void
    {
        $this->expectException(UrlMatchNotFoundException::class);
        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), Argument::any())->willReturn([]);
        $this->webspaceManager->getPortalInformations(Argument::any())->willReturn([]);

        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->request = new InputBag(['post' => 1]);
        $request->query = new InputBag(['get' => 1]);
        $request->attributes = new ParameterBag();

        $this->requestAnalyzer->analyze($request);
        $this->requestAnalyzer->validate($request);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAnalyze')]
    public function testAnalyzeCurrentRequest($config, $expected = []): void
    {
        $this->prepareWebspaceManager($config);

        $request = new Request(['get' => 1], ['post' => 1], [], [], [], ['REQUEST_URI' => $config['path_info']]);
        $request->headers->set('HOST', 'sulu.lo');

        // this request will be analyzed only once
        $this->requestStack->getCurrentRequest()->willReturn($request)->shouldBeCalled();

        $this->requestAnalyzer->analyze($request);
        $this->requestAnalyzer->validate($request);

        $this->assertEquals('de_at', $request->getLocale());
        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocale());
        $this->assertEquals('sulu', $this->requestAnalyzer->getWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getSegment());
        $this->assertEquals($expected['portal_url'], $this->requestAnalyzer->getPortalUrl());
        $this->assertEquals($expected['redirect'], $this->requestAnalyzer->getRedirect());
        $this->assertEquals($expected['resource_locator'], $this->requestAnalyzer->getResourceLocator());
        $this->assertEquals($expected['resource_locator_prefix'], $this->requestAnalyzer->getResourceLocatorPrefix());
    }

    public function testAnalyzeNoCurrentRequest(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->assertNull($this->requestAnalyzer->getAttribute('test'));
    }
}
