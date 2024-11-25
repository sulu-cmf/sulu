<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleCollectionSelectionPropertyResolver;

#[CoversClass(SingleCollectionSelectionPropertyResolver::class)]
class SingleCollectionSelectionPropertyResolverTest extends TestCase
{
    private SingleCollectionSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new SingleCollectionSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params']);

        $this->assertNull($contentView->getContent());
        $this->assertSame([
            'id' => null,
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
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
        yield 'multi_value' => [[1]];
        yield 'object' => [(object) [1]];
    }

    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(int|string $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame((int) $data, $content->getId());
        $this->assertSame('collection', $content->getResourceLoaderKey());

        $this->assertSame(['id' => $data], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: int|string,
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'int' => [1];
        yield 'string' => ['2'];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(1, 'en', ['resourceLoader' => 'custom_collection']);

        $content = $contentView->getContent();

        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame(1, $content->getId());
        $this->assertSame('custom_collection', $content->getResourceLoaderKey());
    }
}
