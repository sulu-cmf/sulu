<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Types\Link;

class LinkTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var ObjectProphecy<LinkProviderPoolInterface>
     */
    private $providerPool;

    /**
     * @var ObjectProphecy<LinkProviderInterface>
     */
    private $provider;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<StructureInterface>
     */
    private $structure;

    public function setUp(): void
    {
        $this->providerPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->structure = $this->prophesize(StructureInterface::class);
        $this->provider = $this->prophesize(LinkProviderInterface::class);

        $this->link = new Link($this->providerPool->reveal());
    }

    public function testGetViewData(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
                'target' => 'testTarget',
                'title' => 'testTitle',
                'anchor' => 'testAnchor',
                'rel' => 'testRel',
            ]);

        $result = $this->link->getViewData($this->property->reveal());

        $this->assertSame([
            'provider' => 'pages',
            'locale' => 'de',
            'target' => 'testTarget',
            'title' => 'testTitle',
            'rel' => 'testRel',
            'href' => '123456',
        ], $result);
    }

    public function testGetViewDataWithoutTargetAndRel(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
            ]);

        $result = $this->link->getViewData($this->property->reveal());

        $this->assertSame([
            'provider' => 'pages',
            'locale' => 'de',
            'href' => '123456',
        ], $result);
    }

    public function testGetViewDataNull(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn(null);

        $result = $this->link->getViewData($this->property->reveal());

        $this->assertSame([], $result);
    }

    public function testGetContentDataWithQuery(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
                'target' => 'testTarget',
                'query' => 'testQuery',
            ]);

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->providerPool->getProvider(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->provider->reveal());

        $linkItem = $this->prophesize(LinkItem::class);
        $linkItem->getUrl()
            ->shouldBeCalled()
            ->willReturn('/test');

        $this->provider->preload(['123456'], 'de', true)
            ->shouldBeCalled()
            ->willReturn([$linkItem]);

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertSame('/test?testQuery', $result);
    }

    public function testGetContentDataWithAnchor(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
                'target' => 'testTarget',
                'anchor' => 'testAnchor',
            ]);

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->providerPool->getProvider(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->provider->reveal());

        $linkItem = $this->prophesize(LinkItem::class);
        $linkItem->getUrl()
            ->shouldBeCalled()
            ->willReturn('/test');

        $this->provider->preload(['123456'], 'de', true)
            ->shouldBeCalled()
            ->willReturn([$linkItem]);

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertSame('/test#testAnchor', $result);
    }

    public function testGetContentDataWithQueryAndAnchor(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
                'target' => 'testTarget',
                'query' => 'testQuery',
                'anchor' => 'testAnchor',
            ]);

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->providerPool->getProvider(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->provider->reveal());

        $linkItem = $this->prophesize(LinkItem::class);
        $linkItem->getUrl()
            ->shouldBeCalled()
            ->willReturn('/test');

        $this->provider->preload(['123456'], 'de', true)
            ->shouldBeCalled()
            ->willReturn([$linkItem]);

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertSame('/test?testQuery#testAnchor', $result);
    }

    public function testGetContentData(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
                'target' => 'testTarget',
            ]);

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $this->providerPool->getProvider(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->provider->reveal());

        $linkItem = $this->prophesize(LinkItem::class);
        $linkItem->getUrl()
            ->shouldBeCalled()
            ->willReturn('/test');

        $this->provider->preload(['123456'], 'de', true)
            ->shouldBeCalled()
            ->willReturn([$linkItem]);

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertSame('/test', $result);
    }

    public function testGetContentDataWithoutProvider(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'locale' => 'de',
                'target' => 'testTarget',
                'anchor' => 'testAnchor',
            ]);

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertNull($result);
    }

    public function testGetContentDataExternalProvider(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '/test/test2',
                'provider' => 'external',
                'locale' => 'de',
                'target' => 'testTarget',
            ]);

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertSame('/test/test2', $result);
    }

    public function testGetContentDataInvalidHref(): void
    {
        $this->property->getValue()
            ->shouldBeCalled()
            ->willReturn([
                'href' => '123456',
                'provider' => 'pages',
                'locale' => 'de',
                'target' => 'testTarget',
                'anchor' => 'testAnchor',
            ]);

        $this->structure->getLanguageCode()
            ->shouldBeCalled()
            ->willReturn('de');

        $this->property->getStructure()
            ->shouldBeCalled()
            ->willReturn($this->structure->reveal());

        $this->providerPool->getProvider(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->provider->reveal());

        $this->provider->preload(['123456'], 'de', true)
            ->shouldBeCalled()
            ->willReturn([]);

        $result = $this->link->getContentData($this->property->reveal());

        $this->assertNull($result);
    }

    public function testImportData(): void
    {
        $value = [
            'href' => '123456',
            'provider' => 'pages',
            'locale' => 'de',
            'target' => 'testTarget',
            'anchor' => 'testAnchor',
        ];

        $property = $this->prophesize(PropertyInterface::class);
        $property->setValue($value)->shouldBeCalled();
        $property->getValue()->willReturn($value);
        $property->getName()->willReturn('test-link');

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('test-link', \json_encode($value))->shouldBeCalled();

        $this->link->importData(
            $node->reveal(),
            $property->reveal(),
            \json_encode($value),
            1,
            'sulu_io',
            'en'
        );
    }
}
