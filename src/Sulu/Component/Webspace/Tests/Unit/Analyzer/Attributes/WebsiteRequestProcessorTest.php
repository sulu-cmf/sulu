<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRequestProcessorTest extends TestCase
{
    use ProphecyTrait;

    private WebsiteRequestProcessor $provider;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<ContentMapperInterface>
     */
    private $contentMapper;

    public function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);

        $this->provider = new WebsiteRequestProcessor(
            $this->webspaceManager->reveal(),
            $this->contentMapper->reveal(),
            'prod'
        );
    }

    public function testProcess(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization('de', 'at');

        $portalInformation1 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            'sulu.lo/de'
        );

        $portalInformation2 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo/de'
        );

        $portalInformation3 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.io/de'
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), 'prod')
            ->willReturn([$portalInformation1]);
        $this->webspaceManager->getPortalInformations('prod')
            ->willReturn([$portalInformation1, $portalInformation2, $portalInformation3]);

        $request = new Request();

        $attributes = $this->provider->process($request, new RequestAttributes(['host' => 'sulu.lo', 'path' => '/test']));

        $this->assertEquals('sulu.lo', $attributes->getAttribute('portalInformation')->getUrl());
        $this->assertEquals('sulu.lo', $portalInformation1->getUrl());
        $this->assertEquals('sulu.lo/de', $portalInformation2->getUrl());
        $this->assertEquals('sulu.io/de', $portalInformation3->getUrl());
    }

    public function testProcessPriority(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization('de', 'at');

        $portalInformation1 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            'sulu.lo',
            false,
            'sulu.lo',
            -5
        );

        $portalInformation2 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            null,
            false,
            'sulu.lo',
            5
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), 'prod')
            ->willReturn([$portalInformation1, $portalInformation2]);
        $this->webspaceManager->getPortalInformations('prod')
            ->willReturn([$portalInformation1, $portalInformation2]);

        $request = new Request();

        $attributes = $this->provider->process($request, new RequestAttributes(['host' => 'sulu.lo', 'path' => '/test']));

        $this->assertEquals($portalInformation2, $attributes->getAttribute('portalInformation'));
        $this->assertEquals('sulu.lo', $portalInformation1->getUrl());
        $this->assertEquals('sulu.lo', $portalInformation2->getUrl());
    }

    public function testProcessSamePriorityDifferentLength(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization('de', 'at');

        $portalInformation1 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo/de',
            null,
            null,
            false,
            'sulu.lo/de',
            5
        );

        $portalInformation2 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            null,
            false,
            'sulu.lo',
            5
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), 'prod')
            ->willReturn([$portalInformation1, $portalInformation2]);
        $this->webspaceManager->getPortalInformations('prod')
            ->willReturn([$portalInformation1, $portalInformation2]);

        $request = new Request();

        $attributes = $this->provider->process($request, new RequestAttributes(['host' => 'sulu.lo', 'path' => 'de']));

        $this->assertEquals($portalInformation1, $attributes->getAttribute('portalInformation'));
    }

    public static function provideValidateData()
    {
        $portalInformationReflection = new \ReflectionClass(PortalInformation::class);
        $portalInformation = $portalInformationReflection->newInstanceWithoutConstructor();

        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization('de', 'at');

        return [
            [['portalInformation' => $portalInformation]],
            [
                ['scheme' => 'http', 'host' => 'sulu.io', 'port' => '80', 'path' => '/', 'requestUri' => '/'],
                UrlMatchNotFoundException::class,
                'There exists no portal for the URL "http://sulu.io"',
                [],
            ],
            [
                ['scheme' => 'http', 'host' => 'sulu.io', 'port' => '80', 'path' => '/', 'requestUri' => '/'],
                UrlMatchNotFoundException::class,
                'There exists no portal for the URL "http://sulu.io/", the url should begin with one of the following Portal Urls: "http://sulu.lo"',
                [
                    new PortalInformation(
                        RequestAnalyzerInterface::MATCH_TYPE_FULL,
                        $webspace,
                        $portal,
                        $localization,
                        'sulu.lo',
                        null,
                        null,
                        false,
                        'sulu.lo',
                        5
                    ),
                ],
            ],
        ];
    }

    /**
     * @param PortalInformation[] $portalInformations
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideValidateData')]
    public function testValidate($attributes, $exception = null, $message = '', $portalInformations = []): void
    {
        if (null !== $exception) {
            $this->expectException($exception, $message);

            $this->webspaceManager->getPortalInformations()
                ->willReturn($portalInformations);
        }

        $this->assertTrue($this->provider->validate(new RequestAttributes($attributes)));
    }
}
