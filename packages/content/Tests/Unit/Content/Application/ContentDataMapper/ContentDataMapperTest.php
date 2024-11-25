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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\ContentDataMapper;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\DataMapperInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentDataMapperTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @param iterable<DataMapperInterface> $dataMappers
     */
    protected function createContentDataMapperInstance(
        iterable $dataMappers
    ): ContentDataMapperInterface {
        return new ContentDataMapper($dataMappers);
    }

    public function testMap(): void
    {
        $dataMapper1 = $this->prophesize(DataMapperInterface::class);
        $dataMapper2 = $this->prophesize(DataMapperInterface::class);

        $contentDataMapper = $this->createContentDataMapperInstance([
            $dataMapper1->reveal(),
            $dataMapper2->reveal(),
        ]);

        $unlocalizedDimensionAttributes = ['stage' => 'draft', 'locale' => null];
        $localizedDimensionAttributes = ['stage' => 'draft', 'locale' => 'en'];

        $data = ['test-key' => 'test-value'];
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContentCollection->getDimensionContent($unlocalizedDimensionAttributes)
            ->willReturn($unlocalizedDimensionContent->reveal())
            ->shouldBeCalled();
        $dimensionContentCollection->getDimensionContent($localizedDimensionAttributes)
            ->willReturn($localizedDimensionContent->reveal())
            ->shouldBeCalled();

        $dataMapper1->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data)
            ->shouldBeCalled();
        $dataMapper2->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data)
            ->shouldBeCalled();

        $contentDataMapper->map($dimensionContentCollection->reveal(), $localizedDimensionAttributes, $data);
    }

    public function testMapNoLocalized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Create unlocalized and localized dimension content.');

        $unlocalizedDimensionAttributes = ['stage' => 'draft', 'locale' => null];
        $localizedDimensionAttributes = ['stage' => 'draft', 'locale' => 'en'];

        $data = ['test-key' => 'test-value'];
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $dimensionContentCollection->getDimensionContent($unlocalizedDimensionAttributes)
            ->willReturn(null)
            ->shouldBeCalled();
        $dimensionContentCollection->getDimensionContent($localizedDimensionAttributes)
            ->willReturn(null)
            ->shouldBeCalled();

        $contentDataMapper = $this->createContentDataMapperInstance([]);
        $contentDataMapper->map($dimensionContentCollection->reveal(), $localizedDimensionAttributes, $data);
    }
}
