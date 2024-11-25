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
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleMediaSelectionPropertyResolver;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

#[CoversClass(SingleMediaSelectionPropertyResolver::class)]
class SingleMediaSelectionPropertyResolverTest extends TestCase
{
    private SingleMediaSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new SingleMediaSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null, 'displayOption' => null], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params']);

        $this->assertNull($contentView->getContent());
        $this->assertSame([
            'id' => null,
            'displayOption' => null,
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data, ?string $expectedDisplayOption = null): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null, 'displayOption' => $expectedDisplayOption], $contentView->getView());
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
        yield 'int_list_not_in_ids' => [[1, 2]];
        yield 'ids_null' => [['ids' => null]];
        yield 'ids_list' => [['ids' => [1, 2]]];
        yield 'id_list' => [['id' => [1, 2]]];
        yield 'display_option_only' => [['displayOption' => 'left'], 'left'];
    }

    /**
     * @param array{
     *     id?: string|int,
     *     displayOption?: string|null,
     * } $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data, ?string $expectedDisplayOption = null): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $id = $data['id'] ?? null;
        if (null !== $id) {
            $id = (int) $id;
            $this->assertInstanceOf(ResolvableResource::class, $content);
            $this->assertSame($id, $content->getId());
            $this->assertSame('media', $content->getResourceLoaderKey());
        }

        $this->assertSame([
            'id' => $id,
            'displayOption' => $expectedDisplayOption,
        ], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         id?: string|int,
     *         displayOption?: string|null,
     *     },
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'int_id' => [['id' => 1]];
        yield 'int_id_with_display_option' => [['id' => 1, 'displayOption' => 'left'], 'left'];
        yield 'string_id' => [['id' => '1']];
        yield 'string_id_with_display_option' => [['id' => '1', 'displayOption' => 'left'], 'left'];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(['id' => 1], 'en', ['resourceLoader' => 'custom_media']);

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame(1, $content->getId());
        $this->assertSame('custom_media', $content->getResourceLoaderKey());
    }
}
