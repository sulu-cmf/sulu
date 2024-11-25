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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\ImageMapPropertyResolver;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Symfony\Component\ErrorHandler\BufferingLogger;

#[CoversClass(ImageMapPropertyResolver::class)]
class ImageMapPropertyResolverTest extends TestCase
{
    private ImageMapPropertyResolver $resolver;

    private BufferingLogger $logger;

    public function setUp(): void
    {
        $this->logger = new BufferingLogger();
        $this->resolver = new ImageMapPropertyResolver(
            $this->logger,
            debug: false,
        );
        $metadataResolverProperty = new PropertyResolverProvider([
            'default' => new DefaultPropertyResolver(),
        ]);
        $metadataResolver = new MetadataResolver($metadataResolverProperty);
        $this->resolver->setMetadataResolver($metadataResolver);
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertSame(['image' => null, 'hotspots' => []], $contentView->getContent());
        $this->assertSame(['imageId' => null, 'hotspots' => []], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params']);

        $this->assertSame(['image' => null, 'hotspots' => []], $contentView->getContent());
        $this->assertSame([
            'imageId' => null,
            'hotspots' => [],
            'custom' => 'params',
        ], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame(['image' => null, 'hotspots' => []], $contentView->getContent());
        $this->assertSame(['imageId' => null, 'hotspots' => []], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
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
        yield 'non_numeric_image_id' => [['imageId' => 'a']];
    }

    /**
     * @param array{
     *     imageId?: string|int,
     *     hotspots?: array<array{
     *         type: string,
     *         hotspot: array{type: string},
     *         ...
     *     }>,
     * } $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en', ['metadata' => $this->createMetadata()]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $imageId = $data['imageId'] ?? null;
        if (null !== $imageId) {
            $imageId = (int) $imageId;
            $image = $content['image'] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $image);
            $this->assertSame($imageId, $image->getId());
            $this->assertSame('media', $image->getResourceLoaderKey());
        }

        $hotspots = $content['hotspots'] ?? [];
        $this->assertIsArray($hotspots);
        $expectedView = [];
        foreach (($data['hotspots'] ?? []) as $key => $hotspot) {
            $hotspot = $hotspots[$key] ?? null;
            $this->assertIsArray($hotspot);
            $this->assertSame($data['hotspots'][$key], $hotspot);
            $expectedView[] = ['title' => []];
        }

        $this->assertSame([
            'imageId' => $imageId,
            'hotspots' => $expectedView,
        ], $contentView->getView());

        $this->assertCount(0, $this->logger->cleanLogs());
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
        yield 'int_id' => [['imageId' => 1]];
        yield 'int_id_with_hotspots' => [
            ['imageId' => 1, 'hotspots' => [['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 1'], ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 2']]],
        ];
        yield 'string_id' => [['imageId' => '1']];
        yield 'string_id_with_hotspots' => [['imageId' => '1', 'hotspots' => [['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 1'], ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 2']]]];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(
            ['imageId' => 1, 'hotspots' => [['type' => 'text', 'title' => 'Title'], ['type' => 'text', 'title' => 'Title']]],
            'en',
            [
                'metadata' => $this->createMetadata(),
                'resourceLoader' => 'custom_media',
            ]
        );

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $image = $content['image'] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $image);
        $this->assertSame(1, $image->getId());
        $this->assertSame('custom_media', $image->getResourceLoaderKey());

        $this->assertSame([
            'imageId' => 1,
            'hotspots' => [],
            'resourceLoader' => 'custom_media',
        ], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    /**
     * @param array{
     *     imageId: int,
     *     hotspots?: array<array{
     *         type?: string,
     *         hotspot?: array{type: string},
     *         ...
     *     }>,
     * } $data
     */
    #[DataProvider('provideUnresolvableHotspotData')]
    public function testResolveUnresolvableHotspotData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en', ['metadata' => $this->createMetadata()]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $image = $content['image'] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $image);
        $this->assertSame(1, $image->getId());
        $this->assertSame('media', $image->getResourceLoaderKey());
        $hotspots = $content['hotspots'] ?? null;
        $this->assertIsArray($hotspots);

        $expectedView = [];
        $expectedCount = \count($data['hotspots'] ?? []);
        $expectedErrorLogs = 0;
        foreach ($data['hotspots'] ?? [] as $hotspot) {
            if (!isset($hotspot['type'])) {
                --$expectedCount;
                continue;
            }
            if (!isset($hotspot['hotspot'])) {
                --$expectedCount;
                continue;
            }
            ++$expectedErrorLogs;
            $expectedView[] = ['title' => []];
        }

        $this->assertCount($expectedCount, $hotspots);

        $this->assertSame(['imageId' => 1, 'hotspots' => $expectedView], $contentView->getView());
        $logs = $this->logger->cleanLogs();
        $this->assertCount($expectedErrorLogs, $logs);
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableHotspotData(): iterable
    {
        yield 'hotspot_with_not_exist_type' => [['imageId' => 1, 'hotspots' => [['type' => 'not_exist', 'hotspot' => ['type' => 'circle'], 'title' => 'Title']]]];
        yield 'hotspot_with_no_type' => [['imageId' => 1, 'hotspots' => [['hotspot' => ['type' => 'circle'], 'title' => 'Title']]]];
        yield 'hotspot_with_no_hotspot' => [['imageId' => 1, 'hotspots' => [['type' => 'not_exist', 'title' => 'Title']]]];
    }

    private function createMetadata(): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata('image');
        $fieldMetadata->setType('image_map');
        $fieldMetadata->setDefaultType('text');

        $textFormMetadata = new FormMetadata();
        $textFormMetadata->setName('text');
        $itemMetadata = new FieldMetadata('title');
        $itemMetadata->setType('text_line');
        $textFormMetadata->addItem($itemMetadata);

        $fieldMetadata->addType($textFormMetadata);

        return $fieldMetadata;
    }
}
