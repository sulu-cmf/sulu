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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentMerger;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\ContentMerger;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ContentMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @param iterable<MergerInterface> $mergers
     */
    protected function createContentMergerInstance(
        iterable $mergers
    ): ContentMergerInterface {
        return new ContentMerger($mergers, new PropertyAccessor());
    }

    public function testMerge(): void
    {
        $merger1 = $this->prophesize(MergerInterface::class);
        $merger2 = $this->prophesize(MergerInterface::class);

        $contentMerger = $this->createContentMergerInstance([
            $merger1->reveal(),
            $merger2->reveal(),
        ]);

        $mergedDimensionContent = $this->prophesize(ExampleDimensionContent::class);

        $resource = $this->prophesize(Example::class);
        $resource->createDimensionContent()
            ->willReturn($mergedDimensionContent->reveal());

        $dimensionContent1 = new ExampleDimensionContent($resource->reveal());
        $dimensionContent1->setLocale(null);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($resource->reveal());
        $dimensionContent1->setLocale('en');
        $dimensionContent1->setStage('draft');

        $merger1->merge($mergedDimensionContent->reveal(), $dimensionContent1)->shouldBeCalled();
        $merger2->merge($mergedDimensionContent->reveal(), $dimensionContent1)->shouldBeCalled();

        $merger1->merge($mergedDimensionContent->reveal(), $dimensionContent2)->shouldBeCalled();
        $merger2->merge($mergedDimensionContent->reveal(), $dimensionContent2)->shouldBeCalled();

        $mergedDimensionContent->setLocale(null) // TODO FIXME find a way to avoid this call (ExampleControllerTest::testPostTriggerUnpublish) currently fails without this
            ->shouldBeCalled();
        $mergedDimensionContent->setLocale('en')
            ->shouldBeCalled();
        $mergedDimensionContent->setStage('draft')
            ->shouldBeCalled();
        $mergedDimensionContent->markAsMerged()
            ->shouldBeCalled();

        $dimensionContentCollection = new DimensionContentCollection([
            $dimensionContent1,
            $dimensionContent2,
        ], [
            'locale' => 'en',
            'stage' => 'draft',
        ], $dimensionContent1::class);

        $this->assertSame(
            $mergedDimensionContent->reveal(),
            $contentMerger->merge($dimensionContentCollection)
        );
    }

    public function testMergeEmptyCollection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected at least one dimensionContent given.');

        $merger1 = $this->prophesize(MergerInterface::class);
        $merger2 = $this->prophesize(MergerInterface::class);

        $contentMerger = $this->createContentMergerInstance([
            $merger1->reveal(),
            $merger2->reveal(),
        ]);

        $dimensionContentCollection = new DimensionContentCollection([], [], ExampleDimensionContent::class);

        $contentMerger->merge($dimensionContentCollection);
    }
}
