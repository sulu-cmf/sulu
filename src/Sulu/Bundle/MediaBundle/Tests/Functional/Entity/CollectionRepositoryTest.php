<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;

class CollectionRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var MediaType
     */
    private $mediaType;

    /**
     * @var array
     */
    private $collectionData = [
        ['1', null, false, 0],
        ['2', 0, false, 0],
        ['3', 1, false, 0],
        ['4', 2, false, 4],
        ['5', 3, false, 0],
        ['6', 3, false, 0],
        ['7', 5, false, 0],
        ['8', 5, false, 0],
        ['9', 2, false, 0],
        ['10', 1, false, 0],
        ['11', 9, false, 0],
        ['12', 9, false, 0],
        ['13', 0, false, 0],
        ['14', 12, false, 0],
        ['15', 12, false, 0],
        ['16', null, true, 0],
        ['17', 15, true, 0],
        ['18', 15, true, 0],
        ['19', 17, true, 0],
        ['20', 17, true, 0],
        ['21', null, false, 0],
    ];

    /**
     * @var Collection[]
     */
    private $collections;

    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();

        $defaultCollectionType = new CollectionType();
        $defaultCollectionType->setName('Default Type');
        $defaultCollectionType->setDescription('Default Collection Type');
        $defaultCollectionType->setKey('collection.default');
        $this->em->persist($defaultCollectionType);

        $systemCollectionType = new CollectionType();
        $systemCollectionType->setName('Default Type');
        $systemCollectionType->setDescription('Default Collection Type');
        $systemCollectionType->setKey(SystemCollectionManagerInterface::COLLECTION_TYPE);
        $this->em->persist($systemCollectionType);

        $this->mediaType = new MediaType();
        $this->mediaType->setName('image');
        $this->mediaType->setDescription('This is an image');
        $this->em->persist($this->mediaType);

        foreach ($this->collectionData as $collection) {
            $this->collections[] = $this->createCollection(
                $collection[0],
                $collection[1],
                $collection[2] ? $systemCollectionType : $defaultCollectionType,
                $collection[3]
            );
        }
        $this->em->flush();

        /* @var CollectionRepository $repository */
        $this->collectionRepository = $this->getContainer()->get('sulu_media.collection_repository');
        $this->collectionRepository->recover();
        $this->em->flush();
    }

    private function createCollection($name, $parent, $collectionType, $numberMedia)
    {
        $collection = new Collection();
        $collectionMeta = new CollectionMeta();
        $collection->setStyle($name);
        $collectionMeta->setTitle($name);
        $collectionMeta->setLocale('en-gb');
        $collection->setType($collectionType);
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        if (null !== $parent) {
            $collection->setParent($this->collections[$parent]);
            $this->collections[$parent]->addChildren($collection);
        }

        $this->addMedia($collection, $numberMedia);

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);

        return $collection;
    }

    private function addMedia(Collection $collection, $numberMedia): void
    {
        for ($i = 0; $i < $numberMedia; ++$i) {
            $media = new Media();
            $media->setCollection($collection);
            $media->setType($this->mediaType);
            $collection->addMedia($media);

            $this->em->persist($media);
        }
    }

    public static function provideTreeData()
    {
        return [
            [0, [0, 1, 12, 15, 20]],
            [15, [0, 15, 16, 17, 20]],
            [20, [0, 15, 20]],
            [1, [0, 1, 2, 9, 12, 15, 20]],
            [16, [0, 15, 16, 17, 20]],
            [3, [0, 1, 2, 3, 4, 5, 8, 9, 12, 15, 20]],
            [13, [0, 1, 12, 13, 14, 15, 20]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTreeData')]
    public function testTree($index, $expectedIndexes): void
    {
        $expected = [];
        $id = $this->collections[$index]->getId();
        foreach ($expectedIndexes as $item) {
            $expected[] = $this->collections[$item];
        }

        $result = $this->collectionRepository->findTree($id, 'de');

        $this->assertEquals($expected, $result);
    }

    public function testFindCollectionSet(): void
    {
        $this->assertCount(21, $this->collectionRepository->findCollectionSet(5));
    }

    public function testFindCollectionSetWithoutSystemCollections(): void
    {
        $this->assertCount(16, $this->collectionRepository->findCollectionSet(5, ['systemCollections' => false]));
    }

    public function testCount(): void
    {
        $this->assertEquals(16, $this->collectionRepository->countCollections(5, ['systemCollections' => false]));
    }

    public function testCountMedia(): void
    {
        $this->assertEquals(4, $this->collectionRepository->countMedia($this->collections[3]));
    }

    public function testCountMediaWithEmptyCollection(): void
    {
        $this->assertEquals(0, $this->collectionRepository->countMedia($this->collections[0]));
    }

    public function testCountWithoutFilters(): void
    {
        $this->assertEquals(21, $this->collectionRepository->countCollections(5));
    }

    public function testGetDescendantIdsById(): void
    {
        $ids = $this->collectionRepository->findDescendantIdsById($this->collections[1]->getId());

        $this->assertCount(10, $ids);

        $this->assertContains($this->collections[2]->getId(), $ids);
        $this->assertContains($this->collections[3]->getId(), $ids);
        $this->assertContains($this->collections[4]->getId(), $ids);
        $this->assertContains($this->collections[5]->getId(), $ids);
        $this->assertContains($this->collections[6]->getId(), $ids);
        $this->assertContains($this->collections[7]->getId(), $ids);
        $this->assertContains($this->collections[8]->getId(), $ids);
        $this->assertContains($this->collections[9]->getId(), $ids);
        $this->assertContains($this->collections[10]->getId(), $ids);
        $this->assertContains($this->collections[11]->getId(), $ids);
    }
}
