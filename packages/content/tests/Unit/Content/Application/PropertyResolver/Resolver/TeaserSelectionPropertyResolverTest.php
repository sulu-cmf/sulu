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

namespace Sulu\Content\Tests\Unit\Content\Application\PropertyResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\PropertyResolver\Resolver\TeaserSelectionPropertyResolver;
use Sulu\Bundle\PageBundle\Teaser\Teaser;

class TeaserSelectionPropertyResolverTest extends TestCase
{
    private TeaserSelectionPropertyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TeaserSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['presentsAs' => null], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['presentsAs' => null, 'custom' => 'params'], $contentView->getView());
    }

    public function testResolveData(): void
    {
        $data = [
            'presentsAs' => 'two-columns',
            'items' => [
                ['id' => '123', 'type' => 'article'],
                ['id' => '456', 'type' => 'page'],
            ],
        ];

        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(2, $content);

        $innerContentView1 = $content[0];
        $this->assertInstanceOf(ContentView::class, $innerContentView1);
        $resolvableResource = $innerContentView1->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvableResource);
        $this->assertSame('article::123', $resolvableResource->getId());
        $this->assertSame('teaser', $resolvableResource->getResourceLoaderKey());
        $this->assertSame(['id' => '123', 'type' => 'article'], $innerContentView1->getView());

        $innerContentView2 = $content[1];
        $this->assertInstanceOf(ContentView::class, $innerContentView2);
        $resolvableResource = $innerContentView2->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvableResource);
        $this->assertSame('page::456', $resolvableResource->getId());
        $this->assertSame('teaser', $resolvableResource->getResourceLoaderKey());
        $this->assertSame(['id' => '456', 'type' => 'page'], $innerContentView2->getView());

        $this->assertSame(['presentsAs' => 'two-columns'], $contentView->getView());
    }

    public function testResolveCustomResourceLoader(): void
    {
        $data = [
            'items' => [
                ['id' => '123', 'type' => 'article'],
            ],
        ];

        $contentView = $this->resolver->resolve($data, 'en', ['resourceLoader' => 'custom_teaser']);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);

        $innerContent = $content[0];
        $this->assertInstanceOf(ContentView::class, $innerContent);
        $resolvable = $innerContent->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame('article::123', $resolvable->getId());
        $this->assertSame('custom_teaser', $resolvable->getResourceLoaderKey());
    }

    public function testResolveWithResourceCallback(): void
    {
        $data = [
            'items' => [
                [
                    'id' => '123',
                    'type' => 'article',
                    'title' => 'Article Title',
                    'description' => 'Article Description',
                    'mediaId' => 11,
                ],
            ],
        ];

        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);

        $innerContent = $content[0];
        $this->assertInstanceOf(ContentView::class, $innerContent);
        $resolvable = $innerContent->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame('article::123', $resolvable->getId());
        $this->assertSame('teaser', $resolvable->getResourceLoaderKey());

        $teaser = new Teaser('123', 'article', 'en', '', '', '', 'http://example.com', 1);
        $mergedTeaser = $resolvable->executeResourceCallback($teaser);
        $this->assertInstanceOf(Teaser::class, $mergedTeaser);
        $this->assertSame('Article Title', $mergedTeaser->getTitle());
        $this->assertSame('Article Description', $mergedTeaser->getDescription());
        $this->assertSame(11, $mergedTeaser->getMediaId());
    }

    public function testGetType(): void
    {
        $this->assertSame('teaser_selection', TeaserSelectionPropertyResolver::getType());
    }
}
