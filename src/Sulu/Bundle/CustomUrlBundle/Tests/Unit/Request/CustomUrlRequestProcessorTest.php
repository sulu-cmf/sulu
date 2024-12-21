<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\CustomUrlBundle\Request\CustomUrlRequestProcessor;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Generator\Generator;
use Sulu\Component\CustomUrl\Manager\CustomUrlManager;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class CustomUrlRequestProcessorTest extends TestCase
{
    use ProphecyTrait;

    public static function dataProvider()
    {
        return [
            ['sulu.io', '/test', null, 'sulu.io/test', false],
            ['sulu.io', '/täst', null, 'sulu.io/täst', false],
            ['sulu.io', '/test.html', null, 'sulu.io/test', false],
            ['sulu.io', '/test.json', null, 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'search=test', 'sulu.io/test?search=test', false],
            ['sulu.io', '/test.json', 'search=test', 'sulu.io/test?search=test', false],
            ['sulu.io', '/test.html', null, 'sulu.io/test', true, true],
            ['sulu.io', '/test.html', null, 'sulu.io/test', true, false, false],
            ['sulu.io', '/test.html', null, 'sulu.io/test', true, false, true, false],
            ['sulu.io', '/test.html', null, 'sulu.io/test', true, false, true, false, true],
            ['sulu.io', '/test.html', null, 'sulu.io/test', true, false, true, true, false, WorkflowStage::PUBLISHED],
            ['sulu.io', '/test.html', null, 'sulu.io/test', true, false, true, true, false, WorkflowStage::TEST],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function testProcess(
        $host,
        $pathInfo,
        $queryString,
        $expectedUrl,
        $exists = true,
        $history = true,
        $published = true,
        $hasTarget = true,
        $noConcretePortal = false,
        $workflowStage = WorkflowStage::PUBLISHED,
        $webspaceKey = 'sulu_io'
    ): void {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);

        $localization = new Localization('de');

        $requestAttributes = new RequestAttributes(['webspace' => $webspace->reveal()]);

        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn($host);
        $request->getPathInfo()->willReturn($pathInfo);
        $request->getQueryString()->willReturn($queryString);

        $customUrlManager = $this->prophesize(CustomUrlManager::class);

        if (!$exists) {
            $customUrlManager->findRouteByUrl($expectedUrl, $webspaceKey)->willReturn(null)->shouldBeCalled();
        } else {
            $routeDocument = $this->prophesize(RouteDocument::class);
            $routeDocument->isHistory()->willReturn($history);
            $routeDocument->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $expectedUrl);

            if ($history) {
                $customUrlManager->findByUrl($expectedUrl, $webspaceKey, 'de')->shouldNotBeCalled();
            } else {
                $customUrl = $this->prophesize(CustomUrlDocument::class);
                $customUrl->isPublished()->willReturn($published);
                $customUrl->getBaseDomain()->willReturn('sulu.lo/*');
                $customUrl->getDomainParts()->willReturn(['prefix' => '', 'suffix' => ['test-1']]);
                $customUrl->getTargetLocale()->willReturn('de');
                $customUrlManager->findByUrl($expectedUrl, $webspaceKey, 'de')->willReturn($customUrl->reveal());

                if ($hasTarget) {
                    $target = $this->prophesize(PageDocument::class);
                    $target->getWorkflowStage()->willReturn($workflowStage);
                    $customUrl->getTargetDocument()->willReturn($target->reveal());
                } else {
                    $customUrl->getTargetDocument()->willReturn(null);
                }
                $routeDocument->getTargetDocument()->willReturn($customUrl->reveal());
            }

            $customUrlManager->findRouteByUrl($expectedUrl, $webspaceKey)->willReturn($routeDocument->reveal());
        }

        $wildcardPortalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_WILDCARD,
            $webspace->reveal(),
            null,
            $localization,
            '*.sulu.lo'
        );

        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace->reveal(),
            null,
            $localization,
            'sulu.lo'
        );

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $webspaceManager->findPortalInformationsByUrl(
            $expectedUrl,
            'prod'
        )->willReturn($noConcretePortal ? [] : [$wildcardPortalInformation]);
        $webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', 'prod')
            ->willReturn([$portalInformation]);

        $generator = $this->prophesize(Generator::class);

        $processor = new CustomUrlRequestProcessor(
            $customUrlManager->reveal(),
            $generator->reveal(),
            $webspaceManager->reveal(),
            'prod'
        );

        $requestAttributes = $processor->process($request->reveal(), $requestAttributes);

        if ($exists && !$noConcretePortal) {
            $this->assertNotNull($requestAttributes->getAttribute('customUrlRoute'));
        } else {
            $this->assertNull($requestAttributes->getAttribute('customUrlRoute'));
        }
    }
}
