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
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\PortalInformationRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class PortalInformationRequestProcessorTest extends TestCase
{
    /**
     * @var PortalInformationRequestProcessor
     */
    private $portalInformationRequestProcessor;

    public function setUp(): void
    {
        $this->portalInformationRequestProcessor = new PortalInformationRequestProcessor();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProcess')]
    public function testProcess($config, $expected = []): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization('de', 'at');

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            $localization,
            $config['portal_url'],
            $config['redirect'],
            false,
            $config['url_expression']
        );

        $request = new Request(
            ['get' => 1],
            ['post' => 1],
            [],
            [],
            [],
            ['HTTP_HOST' => 'sulu.lo']
        );

        $attributes = $this->portalInformationRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation, 'path' => $config['path_info']])
        );

        $this->assertEquals($localization->getLocale(), $request->getLocale());

        $this->assertEquals('de_at', $attributes->getAttribute('localization'));
        $this->assertEquals('sulu', $attributes->getAttribute('webspace')->getKey());
        $this->assertEquals('sulu', $attributes->getAttribute('portal')->getKey());

        $this->assertEquals($expected['portal_url'], $attributes->getAttribute('portalUrl'));
        $this->assertEquals($expected['redirect'], $attributes->getAttribute('redirect'));
        $this->assertEquals($expected['resource_locator'], $attributes->getAttribute('resourceLocator'));
        $this->assertEquals($expected['resource_locator_prefix'], $attributes->getAttribute('resourceLocatorPrefix'));
        $this->assertEquals($expected['url_expression'], $attributes->getAttribute('urlExpression'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProcess')]
    public function testProcessWithoutLocaliziation($config, $expected = []): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');
        $portal->setDefaultLocalization(new Localization('it', 'ch'));

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            null,
            $config['portal_url'],
            $config['redirect'],
            false,
            $config['url_expression']
        );

        $request = new Request(
            ['get' => 1],
            ['post' => 1],
            [],
            [],
            [],
            ['HTTP_HOST' => 'sulu.lo']
        );

        $attributes = $this->portalInformationRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation, 'path' => $config['path_info']])
        );

        $this->assertEquals('it_ch', $request->getLocale());

        $this->assertEquals('it_ch', $attributes->getAttribute('localization'));
        $this->assertEquals('sulu', $attributes->getAttribute('webspace')->getKey());
        $this->assertEquals('sulu', $attributes->getAttribute('portal')->getKey());

        $this->assertEquals($expected['portal_url'], $attributes->getAttribute('portalUrl'));
        $this->assertEquals($expected['redirect'], $attributes->getAttribute('redirect'));
        $this->assertEquals($expected['resource_locator'], $attributes->getAttribute('resourceLocator'));
        $this->assertEquals($expected['resource_locator_prefix'], $attributes->getAttribute('resourceLocatorPrefix'));
        $this->assertEquals($expected['url_expression'], $attributes->getAttribute('urlExpression'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProcessWithFormat')]
    public function testProcessWithFormat($config, $expected = []): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization('de', 'at');

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            $localization,
            $config['portal_url'],
            $config['redirect']
        );

        $request = new Request(
            ['get' => 1],
            ['post' => 1],
            [],
            [],
            [],
            ['HTTP_HOST' => 'sulu.lo']
        );

        $attributes = $this->portalInformationRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation, 'path' => $config['path_info']])
        );

        $this->assertEquals($localization->getLocale(), $request->getLocale());
        if ($expected['format']) {
            $this->assertEquals($expected['format'], $request->getRequestFormat());
        }

        $this->assertEquals('de_at', $attributes->getAttribute('localization'));
        $this->assertEquals('sulu', $attributes->getAttribute('webspace')->getKey());
        $this->assertEquals('sulu', $attributes->getAttribute('portal')->getKey());

        $this->assertEquals($expected['portal_url'], $attributes->getAttribute('portalUrl'));
        $this->assertEquals($expected['redirect'], $attributes->getAttribute('redirect'));
        $this->assertEquals($expected['resource_locator'], $attributes->getAttribute('resourceLocator'));
        $this->assertEquals($expected['resource_locator_prefix'], $attributes->getAttribute('resourceLocatorPrefix'));
        $this->assertEquals($expected['format'], $attributes->getAttribute('format'));
    }

    public function testValidate(): void
    {
        $this->assertTrue($this->portalInformationRequestProcessor->validate(new RequestAttributes()));
    }

    public static function provideProcess()
    {
        return [
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                    'url_expression' => 'sulu.lo/{localization}',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'url_expression' => 'sulu.lo/{localization}',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                    'url_expression' => 'sulu.lo/{localization}',
                ],
                [
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                    'url_expression' => 'sulu.lo/{localization}',
                ],
            ],
        ];
    }

    public static function provideProcessWithFormat()
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
                    'format' => null,
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
}
