<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;

class NavigationTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    public static function activeElementProvider()
    {
        return [
            [false, '/', '/news/item'],
            [true, '/news/item', '/news/item'],
            [true, '/news/item', '/news'],
            [false, '/news/item', '/'],
            [false, '/news/item-1', '/news/item'],
            [false, '/news', '/news/item'],
            [false, '/news', '/product/item'],
            [false, '/news', '/news-1'],
            [false, '/news', '/news-1/item'],
            [true, '/', '/'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('activeElementProvider')]
    public function testActiveElement($expected, $requestPath, $itemPath): void
    {
        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $this->prophesize(NavigationMapperInterface::class)->reveal()
        );

        $this->assertEquals($expected, $extension->navigationIsActiveFunction($requestPath, $itemPath));
    }

    public function testBreadcrumbFunctionDocumentNotFound(): void
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getBreadcrumb('123-123-123', 'sulu_io', 'de')->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->breadcrumbFunction('123-123-123'));
    }

    public function testFlatNavigationFunctionDocumentNotFound(): void
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $requestAnalyzer->getSegment()->willReturn($segment);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('123-123-123', 'sulu_io', 'de', 1, true, null, false, 's')
            ->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->flatNavigationFunction('123-123-123'));
    }

    public function testTreeNavigationFunctionDocumentNotFound(): void
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('w');
        $requestAnalyzer->getSegment()->willReturn($segment);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('123-123-123', 'sulu_io', 'de', 1, false, null, false, 'w')
            ->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->treeNavigationFunction('123-123-123'));
    }

    public function testFlatRootNavigation(): void
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getRootNavigation('sulu_io', 'de', 1, true, 'main', false, null)
            ->shouldBeCalled();

        $extension->flatRootNavigationFunction('main', 1, false);
    }
}
