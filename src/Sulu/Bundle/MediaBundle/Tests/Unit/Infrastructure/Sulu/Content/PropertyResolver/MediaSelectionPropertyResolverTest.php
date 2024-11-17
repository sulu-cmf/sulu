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
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\MediaSelectionPropertyResolver;

#[CoversClass(MediaSelectionPropertyResolver::class)]
class MediaSelectionPropertyResolverTest extends TestCase
{
    private MediaSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new MediaSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => [], 'displayOption' => null], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([
            'ids' => [],
            'displayOption' => null,
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data, ?string $expectedDisplayOption = null): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => [], 'displayOption' => $expectedDisplayOption], $contentView->getView());
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
        yield 'display_option_only' => [['displayOption' => 'left'], 'left'];
    }

    /**
     * @param array{
     *     ids?: array<string|int>,
     *     displayOption?: string|null,
     * } $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data, ?string $expectedDisplayOption = null): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);

        foreach (($data['ids'] ?? []) as $key => $value) {
            $resolvable = $content[$key] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $resolvable);
            $this->assertSame($value, $resolvable->getId());
            $this->assertSame('media', $resolvable->getResourceLoaderKey());
        }

        $this->assertSame([
            'ids' => ($data['ids'] ?? []),
            'displayOption' => $expectedDisplayOption,
        ], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         ids?: array<string|int>,
     *         displayOption?: string|null,
     *     },
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'int_list' => [['ids' => [1, 2]]];
        yield 'int_list_with_display_option' => [['ids' => [1, 2], 'displayOption' => 'left'], 'left'];
        yield 'string_list' => [['ids' => ['1', '2']]];
        yield 'string_list_with_display_option' => [['ids' => ['1', '2'], 'displayOption' => 'left'], 'left'];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(['ids' => [1]], 'en', ['resourceLoader' => 'custom_media']);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $resolvable = $content[0] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(1, $resolvable->getId());
        $this->assertSame('custom_media', $resolvable->getResourceLoaderKey());
    }
}
