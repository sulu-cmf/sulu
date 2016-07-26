<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit\Request;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\CustomUrlBundle\Request\CustomUrlRequestProcessor;
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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class CustomUrlRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function dataProvider()
    {
        return [
            ['sulu.io', '/test', 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'sulu.io/test', true, true],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, false],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, false],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, false, true],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, true, false, WorkflowStage::PUBLISHED],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, true, false, WorkflowStage::TEST],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProcess(
        $host,
        $requestedUri,
        $route,
        $exists = true,
        $history = true,
        $published = true,
        $hasTarget = true,
        $noConcretePortal = false,
        $workflowStage = WorkflowStage::PUBLISHED,
        $webspaceKey = 'sulu_io'
    ) {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);

        $localization = new Localization('de');

        $requestAttributes = new RequestAttributes(['webspace' => $webspace->reveal()]);

        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn($host);
        $request->getRequestUri()->willReturn($requestedUri);
        $request->getPathInfo()->willReturn($requestedUri);
        $request->getScheme()->willReturn('http');
        $request->getUri()->willReturn('http://' . $host . $requestedUri);
        $request->reveal()->query = new ParameterBag();
        $request->reveal()->request = new ParameterBag();

        $customUrlManager = $this->prophesize(CustomUrlManager::class);

        if (!$exists) {
            $customUrlManager->findRouteByUrl($route, $webspaceKey)->willReturn(null);
        } else {
            $routeDocument = $this->prophesize(RouteDocument::class);
            $routeDocument->isHistory()->willReturn($history);
            $routeDocument->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $route);

            if ($history) {
                $target = $this->prophesize(RouteDocument::class);
                $target->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $route . '-1');
                $routeDocument->getTargetDocument()->willReturn($target->reveal());
            } else {
                $customUrl = $this->prophesize(CustomUrlDocument::class);
                $customUrl->isPublished()->willReturn($published);
                $customUrl->getBaseDomain()->willReturn('sulu.lo/*');
                $customUrl->getDomainParts()->willReturn(['prefix' => '', 'suffix' => ['test-1']]);
                $customUrl->getTargetLocale()->willReturn('de');
                $customUrlManager->findByUrl($route, $webspaceKey, 'de')->willReturn($customUrl->reveal());

                if ($hasTarget) {
                    $target = $this->prophesize(PageDocument::class);
                    $target->getWorkflowStage()->willReturn($workflowStage);
                    $customUrl->getTargetDocument()->willReturn($target->reveal());
                } else {
                    $customUrl->getTargetDocument()->willReturn(null);
                }
                $routeDocument->getTargetDocument()->willReturn($customUrl->reveal());
            }

            $customUrlManager->findRouteByUrl($route, $webspaceKey)->willReturn($routeDocument->reveal());
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
            $route,
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
        $processor->process($request->reveal(), $requestAttributes);
    }
}
