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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\PropertyResolver\Resolver\LinkPropertyResolver;

class LinkPropertyResolverTest extends TestCase
{
    private LinkPropertyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new LinkPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame([], $contentView->getView());
    }

    public function testResolveNullParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params']);

        $this->assertNull($contentView->getContent());
        $this->assertSame([
            'custom' => 'params',
        ], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([
            'href' => 'http://example.com',
            'provider' => 'external',
        ], 'en', ['custom' => 'params']);

        $resolvableResource = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvableResource);
        $this->assertSame([
            'href' => 'http://example.com',
            'provider' => 'external',
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame($data, $contentView->getContent());
        $this->assertSame([], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'smart_content' => [['source' => '123']];
        yield 'single_value' => [1];
        yield 'object' => [(object) [1, 2]];
        yield 'no_provider' => [['href' => 123]];
        yield 'no_href' => [['provider' => 'external']];
    }

    public function testResolveCallback(): void
    {
        $contentView = $this->resolver->resolve([
            'href' => '1234-4567-7890',
            'provider' => 'article',
            'query' => 'query=123',
            'anchor' => 'anchor',
        ], 'en');

        $resolvableResource = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvableResource);
        $this->assertSame('article::1234-4567-7890', $resolvableResource->getId());
        $this->assertSame([
            'href' => '1234-4567-7890',
            'provider' => 'article',
            'query' => 'query=123',
            'anchor' => 'anchor',
        ], $contentView->getView());
        $this->assertSame(
            'http://example.com/article?query=123#anchor',
            $resolvableResource->executeResourceCallback(
                new LinkItem('1234-4567-7890', '', 'http://example.com/article', true)
            )
        );
    }

    public function testResolve(): void
    {
        $contentView = $this->resolver->resolve(
            [
                'href' => '123-456-789',
                'provider' => 'article',
                'query' => 'query=123',
                'rel' => 'rel',
                'title' => 'title',
                'anchor' => 'anchor',
                'target' => 'target',
            ],
            'en'
        );

        $resolvableResource = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvableResource);
        $this->assertSame('article::123-456-789', $resolvableResource->getId());
        $this->assertSame([
            'href' => '123-456-789',
            'provider' => 'article',
            'query' => 'query=123',
            'rel' => 'rel',
            'title' => 'title',
            'anchor' => 'anchor',
            'target' => 'target',
        ], $contentView->getView());
    }

    public function testGetType(): void
    {
        $this->assertSame('link', LinkPropertyResolver::getType());
    }
}
