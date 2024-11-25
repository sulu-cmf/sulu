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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\DimensionContentCollectionFactory;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Bundle\ContentBundle\Content\Application\DimensionContentCollectionFactory\DimensionContentCollectionFactory;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DimensionContentCollectionFactoryTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @template T of DimensionContentInterface
     *
     * @param mixed[] $dimensionAttributes
     * @param T[] $existDimensionContents
     */
    protected function createDimensionContentCollectionFactoryInstance(
        array $dimensionAttributes,
        array $existDimensionContents,
        ContentDataMapperInterface $contentDataMapper
    ): DimensionContentCollectionFactory {
        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $dimensionContentRepository->load(Argument::any(), Argument::any())->willReturn(
            new DimensionContentCollection(
                $existDimensionContents,
                $dimensionAttributes,
                ExampleDimensionContent::class
            )
        );

        return new DimensionContentCollectionFactory(
            $dimensionContentRepository->reveal(),
            $contentDataMapper,
            new PropertyAccessor()
        );
    }

    public function testCreateWithExistingDimensionContent(): void
    {
        $contentRichEntity = $this->prophesize(Example::class);
        $dimensionContent1 = new ExampleDimensionContent($contentRichEntity->reveal());
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($contentRichEntity->reveal());
        $dimensionContent2->setStage('draft');
        $dimensionContent2->setLocale('de');

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $attributes = [
            'locale' => 'de',
            'stage' => 'draft',
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(
            Argument::that(
                function(DimensionContentCollectionInterface $collection) use ($dimensionContent1, $dimensionContent2) {
                    return [$dimensionContent1, $dimensionContent2] === \iterator_to_array($collection);
                }
            ),
            $attributes,
            $data
        )->shouldBeCalled();

        $dimensionContentCollectionFactoryInstance = $this->createDimensionContentCollectionFactoryInstance(
            $attributes,
            [
                $dimensionContent1,
                $dimensionContent2,
            ],
            $contentDataMapper->reveal()
        );

        $dimensionContentCollection = $dimensionContentCollectionFactoryInstance->create(
            $contentRichEntity->reveal(),
            $attributes,
            $data
        );

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(ExampleDimensionContent::class, $dimensionContentCollection->getDimensionContentClass());
        $this->assertSame($attributes, $dimensionContentCollection->getDimensionAttributes());
        $this->assertSame(
            [$dimensionContent1, $dimensionContent2],
            \iterator_to_array($dimensionContentCollection)
        );
    }

    public function testCreateWithoutExistingDimensionContent(): void
    {
        $contentRichEntity = $this->prophesize(Example::class);
        $dimensionContent1 = new ExampleDimensionContent($contentRichEntity->reveal());
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($contentRichEntity->reveal());
        $dimensionContent2->setStage('draft');
        $dimensionContent2->setLocale('de');

        $contentRichEntity->createDimensionContent()->shouldBeCalledTimes(2)
            ->willReturn($dimensionContent1, $dimensionContent2);
        $contentRichEntity->addDimensionContent($dimensionContent1)->shouldBeCalled();
        $contentRichEntity->addDimensionContent($dimensionContent2)->shouldBeCalled();

        $attributes = [
            'locale' => 'de',
            'stage' => 'draft',
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(
            Argument::that(
                function(DimensionContentCollectionInterface $collection) use ($dimensionContent1, $dimensionContent2) {
                    return [$dimensionContent1, $dimensionContent2] === \iterator_to_array($collection);
                }
            ),
            $attributes,
            $data
        )->shouldBeCalled();

        $dimensionContentCollectionFactoryInstance = $this->createDimensionContentCollectionFactoryInstance(
            $attributes,
            [
            ],
            $contentDataMapper->reveal()
        );

        $dimensionContentCollection = $dimensionContentCollectionFactoryInstance->create(
            $contentRichEntity->reveal(),
            $attributes,
            $data
        );

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(ExampleDimensionContent::class, $dimensionContentCollection->getDimensionContentClass());
        $this->assertSame($attributes, $dimensionContentCollection->getDimensionAttributes());
        $this->assertSame(
            [$dimensionContent1, $dimensionContent2],
            \iterator_to_array($dimensionContentCollection)
        );
        $this->assertSame('de', $dimensionContent1->getGhostLocale());
        $this->assertSame(['de'], $dimensionContent1->getAvailableLocales());
    }

    public function testCreateWithoutExistingLocalizedDimensionContent(): void
    {
        $contentRichEntity = $this->prophesize(Example::class);
        $dimensionContent1 = new ExampleDimensionContent($contentRichEntity->reveal());
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($contentRichEntity->reveal());
        $dimensionContent2->setStage('draft');
        $dimensionContent2->setLocale('de');

        $contentRichEntity->createDimensionContent()
            ->shouldBeCalled()
            ->willReturn($dimensionContent2);
        $contentRichEntity->addDimensionContent($dimensionContent2)->shouldBeCalled();

        $attributes = [
            'locale' => 'de',
            'stage' => 'draft',
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(
            Argument::that(
                function(DimensionContentCollectionInterface $collection) use ($dimensionContent1, $dimensionContent2) {
                    return [$dimensionContent1, $dimensionContent2] === \iterator_to_array($collection);
                }
            ),
            $attributes,
            $data
        )->shouldBeCalled();
        $dimensionContentCollectionFactoryInstance = $this->createDimensionContentCollectionFactoryInstance(
            $attributes,
            [
                $dimensionContent1,
            ],
            $contentDataMapper->reveal()
        );

        $dimensionContentCollection = $dimensionContentCollectionFactoryInstance->create(
            $contentRichEntity->reveal(),
            $attributes,
            $data
        );

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(ExampleDimensionContent::class, $dimensionContentCollection->getDimensionContentClass());
        $this->assertSame($attributes, $dimensionContentCollection->getDimensionAttributes());
        $this->assertSame(
            [$dimensionContent1, $dimensionContent2],
            \iterator_to_array($dimensionContentCollection)
        );
    }
}
