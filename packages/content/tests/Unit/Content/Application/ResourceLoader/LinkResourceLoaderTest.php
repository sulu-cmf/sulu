<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ResourceLoader\Loader\LinkResourceLoader;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;

class LinkResourceLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<LinkProviderPoolInterface>
     */
    private ObjectProphecy $linkProviderPool;

    private LinkResourceLoader $loader;

    protected function setUp(): void
    {
        $this->linkProviderPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->loader = new LinkResourceLoader($this->linkProviderPool->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('link', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $link1 = new LinkItem(
            '123-123-123',
            'Page',
            'https://sulu.io/page',
            true
        );
        $link2 = new LinkItem(
            '321-321-321',
            'Article',
            'https://sulu.rocks/article',
            true
        );

        $pageLinkProvider = $this->prophesize(LinkProviderInterface::class);
        $pageLinkProvider->preload(
            ['123-123-123'],
            'en'
        )
            ->willReturn([$link1])
            ->shouldBeCalled();

        $articleLinkProvider = $this->prophesize(LinkProviderInterface::class);
        $articleLinkProvider->preload(
            ['321-321-321'],
            'en'
        )
            ->willReturn([$link2])
            ->shouldBeCalled();

        $this->linkProviderPool->getProvider('page')->willReturn($pageLinkProvider->reveal());
        $this->linkProviderPool->getProvider('article')->willReturn($articleLinkProvider->reveal());

        $result = $this->loader->load(['page::123-123-123', 'article::321-321-321'], 'en', []);

        $this->assertSame([
            'page::123-123-123' => $link1,
            'article::321-321-321' => $link2,
        ], $result);
    }
}
