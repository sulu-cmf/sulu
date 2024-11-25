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

namespace Sulu\Content\Tests\Functional\Infrastructure\Sulu\Search;

use Massive\Bundle\SearchBundle\Search\Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\ObjectToDocumentConverter;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Search\ContentSearchMetadataProvider;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContentSearchMetadataProviderTest extends SuluTestCase
{
    use CreateExampleTrait;

    /**
     * @var ContentManagerInterface
     */
    private $contentManager;

    /**
     * @var ObjectToDocumentConverter
     */
    private $objectToDocumentConverter;

    /**
     * @var ContentSearchMetadataProvider<ExampleDimensionContent, Example>
     */
    private $searchMetadataProvider;

    /**
     * @var Example
     */
    private static $example1;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();

        self::$example1 = static::createExample([
            'en' => [
                'draft' => [
                    'title' => 'example-1',
                ],
            ],
        ]);

        static::getEntityManager()->flush();
    }

    protected function setUp(): void
    {
        $this->contentManager = $this->getContainer()->get('sulu_content.content_manager');
        $this->objectToDocumentConverter = $this->getContainer()->get('massive_search.object_to_document_converter');
        $this->searchMetadataProvider = $this->getContainer()->get('example_test.example_search_metadata_provider');
    }

    public function testGetMetadataForObject(): void
    {
        $dimensionContent = $this->contentManager->resolve(self::$example1, [
            'stage' => DimensionContentInterface::STAGE_DRAFT,
            'locale' => 'en',
        ]);

        $this->assertInstanceOf(
            ClassMetadata::class,
            $this->searchMetadataProvider->getMetadataForObject($dimensionContent)
        );
    }

    public function testGetMetadataForObjectNoDimensionContent(): void
    {
        $this->assertNull(
            $this->searchMetadataProvider->getMetadataForObject(new \stdClass())
        );
    }

    public function testGetMetadataForObjectNotMerged(): void
    {
        $this->assertNull(
            $this->searchMetadataProvider->getMetadataForObject(
                (object) self::$example1->getDimensionContents()->first()
            )
        );
    }

    public function testGetAllMetadata(): void
    {
        $allMetadata = $this->searchMetadataProvider->getAllMetadata();

        $this->assertIsArray($allMetadata);
        foreach ($allMetadata as $metadata) {
            $this->assertInstanceOf(ClassMetadata::class, $metadata);
        }
        $this->assertCount(4, $allMetadata);
    }

    public function testGetMetadataForDocument(): void
    {
        $dimensionContent = $this->contentManager->resolve(self::$example1, [
            'stage' => DimensionContentInterface::STAGE_DRAFT,
            'locale' => 'en',
        ]);

        $metadata = $this->searchMetadataProvider->getMetadataForObject($dimensionContent);
        $this->assertNotNull($metadata);
        $allIndexMetadata = $metadata->getIndexMetadatas();
        $indexMetadata = $allIndexMetadata[\array_key_first($allIndexMetadata)];

        $document = $this->objectToDocumentConverter->objectToDocument($indexMetadata, $dimensionContent);
        $documentMetadata = $this->searchMetadataProvider->getMetadataForDocument($document);
        $this->assertNotNull($documentMetadata);

        $this->assertSame(
            $metadata->serialize(),
            $documentMetadata->serialize()
        );
    }
}
