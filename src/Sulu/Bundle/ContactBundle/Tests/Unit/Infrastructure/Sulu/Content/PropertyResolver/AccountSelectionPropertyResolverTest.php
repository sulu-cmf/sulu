<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\AccountSelectionPropertyResolver;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

#[CoversClass(AccountSelectionPropertyResolver::class)]
class AccountSelectionPropertyResolverTest extends TestCase
{
    private AccountSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new AccountSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([
            'ids' => [],
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
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
    }

    /**
     * @param array<string|int> $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        foreach ($data as $key => $value) {
            $resolvable = $content[$key] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $resolvable);
            $this->assertSame($value, $resolvable->getId());
            $this->assertSame('account', $resolvable->getResourceLoaderKey());
        }

        $this->assertSame(['ids' => $data], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: array<string|int>,
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'int_list' => [[1, 2]];
        yield 'string_list' => [['1', '2']];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve([1], 'en', ['resourceLoader' => 'custom_account']);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $resolvable = $content[0] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(1, $resolvable->getId());
        $this->assertSame('custom_account', $resolvable->getResourceLoaderKey());
    }
}
