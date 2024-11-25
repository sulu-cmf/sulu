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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentPersister;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersister;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\DimensionContentCollectionFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentPersisterTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentPersisterInstance(
        DimensionContentCollectionFactoryInterface $dimensionContentCollectionFactory,
        ContentMergerInterface $contentMerger
    ): ContentPersisterInterface {
        return new ContentPersister(
            $dimensionContentCollectionFactory,
            $contentMerger
        );
    }

    public function testPersist(): void
    {
        $attributes = [
            'locale' => 'de',
        ];
        $data = [
            'data' => 'value',
        ];
        $expectedAttributes = [
            'locale' => 'de',
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $example = new Example();
        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setLocale(null);
        $dimensionContent1->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage(DimensionContentInterface::STAGE_DRAFT);

        $dimensionContentCollection = new DimensionContentCollection([
            $dimensionContent1,
            $dimensionContent2,
        ], $expectedAttributes, ExampleDimensionContent::class);

        $dimensionContentCollectionFactory = $this->prophesize(DimensionContentCollectionFactoryInterface::class);
        $dimensionContentCollectionFactory->create($example, $attributes, $data)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $mergedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentMerger->merge($dimensionContentCollection)->willReturn($mergedDimensionContent->reveal())->shouldBeCalled();

        $createContentMessageHandler = $this->createContentPersisterInstance(
            $dimensionContentCollectionFactory->reveal(),
            $contentMerger->reveal()
        );

        $this->assertSame(
            $mergedDimensionContent->reveal(),
            $createContentMessageHandler->persist($example, $data, $attributes)
        );
    }
}
