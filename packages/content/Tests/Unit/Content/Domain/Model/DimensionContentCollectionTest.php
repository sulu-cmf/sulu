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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class DimensionContentCollectionTest extends TestCase
{
    /**
     * @param ExampleDimensionContent[] $dimensionContents
     * @param mixed[] $dimensionAttributes
     *
     * @return DimensionContentCollectionInterface<ExampleDimensionContent>
     */
    protected function createDimensionContentCollectionInstance(
        array $dimensionContents,
        array $dimensionAttributes
    ): DimensionContentCollectionInterface {
        return new DimensionContentCollection($dimensionContents, $dimensionAttributes, ExampleDimensionContent::class);
    }

    public function testCount(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = ['locale' => 'de'];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent1,
            $dimensionContent2,
        ], $attributes);

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(2, \count($dimensionContentCollection)); // @phpstan-ignore-line
        $this->assertSame(2, $dimensionContentCollection->count()); // @phpstan-ignore-line
    }

    public function testSortedByAttributes(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = ['locale' => 'de'];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent2,
            $dimensionContent1,
        ], $attributes);

        $this->assertSame([
            $dimensionContent1,
            $dimensionContent2,
        ], \iterator_to_array($dimensionContentCollection));
    }

    public function testIterator(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = ['locale' => 'de'];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent1,
            $dimensionContent2,
        ], $attributes);

        $this->assertSame([
            $dimensionContent1,
            $dimensionContent2,
        ], \iterator_to_array($dimensionContentCollection));
    }

    public function testGetDimensionContentClass(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = ['locale' => 'de'];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent1,
            $dimensionContent2,
        ], $attributes);

        $this->assertSame(
            ExampleDimensionContent::class,
            $dimensionContentCollection->getDimensionContentClass()
        );
    }

    public function testGetDimensionAttributes(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = [
            'locale' => 'de',
        ];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent1,
            $dimensionContent2,
        ], $attributes);

        $this->assertSame(
            [
                'locale' => 'de',
                'stage' => 'draft',
            ],
            $dimensionContentCollection->getDimensionAttributes()
        );
    }

    public function testGetDimensionContent(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = ['locale' => 'de'];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent1,
            $dimensionContent2,
        ], $attributes);

        $this->assertSame(
            $dimensionContent2,
            $dimensionContentCollection->getDimensionContent($attributes)
        );
    }

    public function testGetDimensionContentNotExist(): void
    {
        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage('draft');

        $attributes = ['locale' => 'de'];

        $dimensionContentCollection = $this->createDimensionContentCollectionInstance([
            $dimensionContent1,
            $dimensionContent2,
        ], $attributes);

        $this->assertNull($dimensionContentCollection->getDimensionContent(['locale' => 'en']));
    }
}
