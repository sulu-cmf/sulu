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
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\ContactAccountSelectionPropertyResolver;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ResolvableResource;

#[CoversClass(ContactAccountSelectionPropertyResolver::class)]
class ContactAccountSelectionPropertyResolverTest extends TestCase
{
    private ContactAccountSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new ContactAccountSelectionPropertyResolver();
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
        yield 'unknown_prefix' => [['e1', 'b2']];
    }

    /**
     * @param string[] $data
     * @param string[] $expectedResourceLoaderKeys
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data, array $expectedResourceLoaderKeys): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        foreach ($data as $key => $value) {
            $resolvable = $content[$key] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $resolvable);
            $this->assertSame((int) \substr($value, 1), $resolvable->getId());
            $expectedResourceLoaderKey = $expectedResourceLoaderKeys[$key] ?? null;
            $this->assertSame($expectedResourceLoaderKey, $resolvable->getResourceLoaderKey());
        }

        $this->assertSame(['ids' => $data], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: string[],
     *     1: string[],
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[], []];
        yield 'contact_list' => [['c1', 'a2'], ['contact', 'account']];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(['c1', 'a2'], 'en', [
            'contactResourceLoader' => 'custom_contact',
            'accountResourceLoader' => 'custom_account',
        ]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);

        $resolvable = $content[0] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(1, $resolvable->getId());
        $this->assertSame('custom_contact', $resolvable->getResourceLoaderKey());

        $resolvable = $content[1] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(2, $resolvable->getId());
        $this->assertSame('custom_account', $resolvable->getResourceLoaderKey());
    }
}
