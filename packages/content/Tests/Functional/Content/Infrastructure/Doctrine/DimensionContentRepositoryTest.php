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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Doctrine;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class DimensionContentRepositoryTest extends SuluTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
    }

    public function createContentDimensionRepository(): DimensionContentRepositoryInterface
    {
        return self::getContainer()->get('sulu_content.dimension_content_repository');
    }

    public function testLoadExistAll(): void
    {
        // prepare database
        $contentRichEntity = $this->createContentRichEntity();
        $dimensionContent1 = $this->createContentDimension($contentRichEntity, []);
        $dimensionContent2 = $this->createContentDimension($contentRichEntity, ['locale' => 'de']);
        $this->createContentDimension($contentRichEntity, ['locale' => 'en']);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // test functionality
        $dimensionContentRepository = $this->createContentDimensionRepository();
        $dimensionContentCollection = $dimensionContentRepository->load($contentRichEntity, ['locale' => 'de']);

        // assert result
        $this->assertCount(2, $dimensionContentCollection);

        $this->assertSame([
            $dimensionContent1->getId(),
            $dimensionContent2->getId(),
        ], \array_map(function(ExampleDimensionContent $dimensionContent) {
            return $dimensionContent->getId();
        }, \iterator_to_array($dimensionContentCollection)));
    }

    public function testLoadOneNotExist(): void
    {
        // prepare database
        $contentRichEntity = $this->createContentRichEntity();
        $dimensionContent1 = $this->createContentDimension($contentRichEntity);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // test functionality
        $dimensionContentRepository = $this->createContentDimensionRepository();
        $dimensionContentCollection = $dimensionContentRepository->load($contentRichEntity, ['locale' => 'de']);

        // assert result
        $this->assertCount(1, $dimensionContentCollection);

        $this->assertSame([
            $dimensionContent1->getId(),
        ], \array_map(function(ExampleDimensionContent $dimensionContent) {
            return $dimensionContent->getId();
        }, \iterator_to_array($dimensionContentCollection)));
    }

    public function testLoadExistOrderedDifferent(): void
    {
        // prepare database
        $contentRichEntity = $this->createContentRichEntity();
        // First create the dimension 2 to test if its still the last dimension
        $dimensionContent2 = $this->createContentDimension($contentRichEntity, ['locale' => 'de']);
        $dimensionContent1 = $this->createContentDimension($contentRichEntity);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // test functionality
        $dimensionContentRepository = $this->createContentDimensionRepository();
        $dimensionContentCollection = $dimensionContentRepository->load($contentRichEntity, ['locale' => 'de']);

        // assert result
        $this->assertCount(2, $dimensionContentCollection);

        $this->assertSame([
            $dimensionContent1->getId(),
            $dimensionContent2->getId(), // Dimension 2 should be the last one in this case
        ], \array_map(function(ExampleDimensionContent $dimensionContent) {
            return $dimensionContent->getId();
        }, \iterator_to_array($dimensionContentCollection)));
    }

    private function createContentRichEntity(): Example
    {
        $example = new Example();
        $this->getEntityManager()->persist($example);

        return $example;
    }

    /**
     * @param array{
     *     stage?: string,
     *     locale?: string,
     * } $dimensionAttributes
     */
    private function createContentDimension(Example $example, array $dimensionAttributes = []): ExampleDimensionContent
    {
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setStage($dimensionAttributes['stage'] ?? DimensionContentInterface::STAGE_DRAFT);
        $exampleDimension->setLocale($dimensionAttributes['locale'] ?? null);

        $this->getEntityManager()->persist($exampleDimension);

        return $exampleDimension;
    }
}
