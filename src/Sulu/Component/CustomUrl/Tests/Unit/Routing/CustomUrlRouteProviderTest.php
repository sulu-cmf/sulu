<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Routing\CustomUrlRouteProvider;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class CustomUrlRouteProviderTest extends TestCase
{
    use ProphecyTrait;

    public static function dataProvider()
    {
        return [
            ['sulu.io', '/test', null, 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', false],
            ['sulu.io', '/test.json', 'json', 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', false],
            ['sulu.io', '/test', null, 'sulu.io/test', true, true, 'sulu.io/test-1'],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', true, true, 'sulu.io/test-1.html'],
            ['sulu.io', '/test.json', 'json', 'sulu.io/test', true, true, 'sulu.io/test-1.json'],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', true, false, null, false],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', true, false, null, true, false],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', true, false, null, true, true, WorkflowStage::PUBLISHED],
            ['sulu.io', '/test.html', 'html', 'sulu.io/test', true, false, null, true, true, WorkflowStage::TEST],
            ['sulu.io', '/käße.html', 'html', 'sulu.io/kaese', true, false, null, true, true, WorkflowStage::TEST],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function testGetRouteCollectionForRequest(
        $host,
        $pathInfo,
        $requestFormat,
        $expectedRoutePath,
        $exists = true,
        $history = true,
        $expectedHistoryRedirectUrl = null,
        $published = true,
        $hasTarget = true,
        $workflowStage = WorkflowStage::PUBLISHED,
        $webspaceKey = 'sulu_io'
    ): void {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $request = $this->prophesize(Request::class);

        $requestAnalyzer->getWebspace()->willReturn($webspace->reveal());
        $requestAnalyzer->getAttribute('localization')->willReturn(new Localization('de'));

        $pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom-urls/routes');

        $request->getHost()->willReturn($host);
        $request->getPathInfo()->willReturn($pathInfo);
        $request->getScheme()->willReturn('http');
        $request->getRequestFormat(null)->willReturn($requestFormat);

        if (!$exists) {
            $requestAnalyzer->getAttribute('customUrlRoute')->willReturn(null);
            $requestAnalyzer->getAttribute('customUrl')->willReturn(null);
        } else {
            $routeDocument = $this->prophesize(RouteDocument::class);
            $routeDocument->isHistory()->willReturn($history);
            $routeDocument->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $expectedRoutePath);

            if ($history) {
                $target = $this->prophesize(RouteDocument::class);
                $target->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $expectedRoutePath . '-1');
                $routeDocument->getTargetDocument()->willReturn($target->reveal());
                $requestAnalyzer->getAttribute('customUrl')->willReturn(null);

                $customUrl = $this->prophesize(CustomUrlDocument::class);
                $customUrl->isRedirect()->willReturn(false);
                $target->getTargetDocument()->willReturn($customUrl->reveal());
            } else {
                $customUrl = $this->prophesize(CustomUrlDocument::class);
                $customUrl->isPublished()->willReturn($published);
                $customUrl->getTargetLocale()->willReturn('de');
                $requestAnalyzer->getAttribute('customUrl')->willReturn($customUrl->reveal());

                if ($hasTarget) {
                    $target = $this->prophesize(PageDocument::class);
                    $target->getWorkflowStage()->willReturn($workflowStage);
                    $customUrl->getTargetDocument()->willReturn($target->reveal());
                } else {
                    $customUrl->getTargetDocument()->willReturn(null);
                }

                $routeDocument->getTargetDocument()->willReturn($customUrl->reveal());
            }

            $requestAnalyzer->getAttribute('customUrlRoute')->willReturn($routeDocument->reveal());
        }

        $provider = new CustomUrlRouteProvider(
            $requestAnalyzer->reveal(),
            $pathBuilder->reveal(),
            'prod'
        );

        $collection = $provider->getRouteCollectionForRequest($request->reveal());

        if (!$exists || !$published || WorkflowStage::TEST === $workflowStage) {
            $this->assertCount(0, $collection);

            return;
        }

        $this->assertCount(1, $collection);
        $all = $collection->all();
        $defaults = \array_pop($all)->getDefaults();

        if ($history) {
            $this->assertEquals(
                [
                    '_controller' => 'sulu_website.redirect_controller::redirectAction',
                    '_finalized' => true,
                    'url' => 'http://' . $expectedHistoryRedirectUrl,
                ],
                $defaults
            );

            return;
        }

        $this->assertEquals(
            [
                '_custom_url' => $customUrl->reveal(),
                '_webspace' => $webspace->reveal(),
                '_environment' => 'prod',
            ],
            $defaults
        );
    }
}
