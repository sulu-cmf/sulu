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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Preview;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Infrastructure\Sulu\Preview\PreviewDimensionContentCollection;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class PreviewDimensionContentCollectionTest extends TestCase
{
    /**
     * @template T of ExampleDimensionContent
     *
     * @param T|null $previewDimensionContent
     *
     * @return PreviewDimensionContentCollection<T|ExampleDimensionContent>
     */
    protected function createPreviewDimensionContentCollection(
        ?ExampleDimensionContent $previewDimensionContent = null,
        string $locale = 'en'
    ): PreviewDimensionContentCollection {
        return new PreviewDimensionContentCollection(
            $previewDimensionContent ?: new ExampleDimensionContent(new Example()),
            $locale
        );
    }

    public function testGetDimensionContentClass(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $previewDimensionContentCollection = $this->createPreviewDimensionContentCollection($dimensionContent);

        $this->assertSame(
            ExampleDimensionContent::class,
            $previewDimensionContentCollection->getDimensionContentClass()
        );
    }

    public function testGetDimensionContent(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $previewDimensionContentCollection = $this->createPreviewDimensionContentCollection($dimensionContent);

        $this->assertSame(
            $dimensionContent,
            $previewDimensionContentCollection->getDimensionContent([])
        );
        $this->assertSame(
            $dimensionContent,
            $previewDimensionContentCollection->getDimensionContent(['stage' => 'draft', 'locale' => null])
        );
        $this->assertSame(
            $dimensionContent,
            $previewDimensionContentCollection->getDimensionContent(['stage' => 'draft', 'locale' => 'en'])
        );
    }

    public function testGetDimensionAttributes(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $previewDimensionContentCollection = $this->createPreviewDimensionContentCollection(
            $dimensionContent,
            'es'
        );

        $this->assertSame(
            ['locale' => 'es', 'stage' => 'draft'],
            $previewDimensionContentCollection->getDimensionAttributes()
        );
    }

    public function testGetIterator(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $previewDimensionContentCollection = $this->createPreviewDimensionContentCollection($dimensionContent);

        $this->assertSame(
            [$dimensionContent],
            \iterator_to_array($previewDimensionContentCollection)
        );
    }

    public function testGetCount(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $previewDimensionContentCollection = $this->createPreviewDimensionContentCollection($dimensionContent);

        $this->assertCount(1, $previewDimensionContentCollection);
    }
}
