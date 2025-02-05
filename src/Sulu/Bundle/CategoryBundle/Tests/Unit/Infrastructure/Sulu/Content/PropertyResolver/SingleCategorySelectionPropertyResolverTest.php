<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleCategorySelectionPropertyResolver;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

#[CoversClass(SingleCategorySelectionPropertyResolver::class)]
class SingleCategorySelectionPropertyResolverTest extends TestCase
{
    private SingleCategorySelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new SingleCategorySelectionPropertyResolver();
    }

    public function testResolveNull(): void
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

        $this->assertSame(null, $contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
    }

    /**
     * @return iterable<mixed[]>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'smart_content' => [['source' => '123']];
        yield 'single_string_value' => ['1'];
        yield 'object' => [(object) [1, 2]];
    }

    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(int $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $resolvable = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame($data, $resolvable->getId());
        $this->assertSame('category', $resolvable->getResourceLoaderKey());

        $this->assertSame(['id' => $data], $contentView->getView());
    }

    /**
     * @return iterable<mixed[]>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'int' => [1];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(1, 'en', ['resourceLoader' => 'custom_category']);

        $resolvable = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(1, $resolvable->getId());
        $this->assertSame('custom_category', $resolvable->getResourceLoaderKey());
    }
}
