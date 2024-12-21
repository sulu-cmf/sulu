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

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMetaRepository;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class FileVersionMetaRepositoryTest extends SuluTestCase
{
    /**
     * @var FileVersionMetaRepository
     */
    private $fileVersionMetaRepository;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MediaType
     */
    private $mediaType;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var CollectionType
     */
    private $collectionType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->fileVersionMetaRepository = $this->em->getRepository(FileVersionMeta::class);

        $this->collectionType = new CollectionType();
        $this->collectionType->setName('image');

        $this->collection = new Collection();
        $this->collection->setType($this->collectionType);

        $this->mediaType = new MediaType();
        $this->mediaType->setName('image');

        $this->em->persist($this->collectionType);
        $this->em->persist($this->mediaType);
        $this->em->persist($this->collection);
        $this->em->flush();
    }

    public function testFindLatestWithoutSecurity(): void
    {
        $this->createFile('Old Title 1', 'New Title 1');
        $this->createFile('Old Title 2', 'New Title 2');

        $this->em->flush();

        $titles = \array_map(
            function(FileVersionMeta $fileVersionMeta) {
                return $fileVersionMeta->getTitle();
            },
            $this->fileVersionMetaRepository->findLatestWithoutSecurity()
        );

        $this->assertContains('New Title 1', $titles);
        $this->assertContains('New Title 2', $titles);
        $this->assertNotContains('Old Title 1', $titles);
        $this->assertNotContains('Old Title 2', $titles);
    }

    public function testFindByCollectionId(): void
    {
        $collection = new Collection();
        $collection->setType($this->collectionType);

        $this->em->persist($collection);

        $this->createFile('Old Title 1', 'New Title 1');
        $this->createFile('Old Title 2', 'New Title 2');
        $this->createFile('Old Title 3', 'New Title 3', $collection);

        $this->em->flush();

        $titles = \array_map(
            function(FileVersionMeta $fileVersionMeta) {
                return $fileVersionMeta->getTitle();
            },
            $this->fileVersionMetaRepository->findByCollectionId($this->collection->getId())
        );

        $this->assertContains('New Title 1', $titles);
        $this->assertContains('New Title 2', $titles);
        $this->assertContains('Old Title 1', $titles);
        $this->assertContains('Old Title 2', $titles);
        $this->assertNotContains('New Title 3', $titles);
        $this->assertNotContains('Old Title 3', $titles);
    }

    private function createFile($oldTitle, $newTitle, $collection = null): void
    {
        $media = new Media();
        $media->setType($this->mediaType);
        $media->setCollection($collection ?: $this->collection);

        $file = new File();
        $file->setVersion(2);
        $file->setMedia($media);

        $this->createFileVersion($file, $oldTitle, 1);
        $this->createFileVersion($file, $newTitle, 2);

        $this->em->persist($file);
        $this->em->persist($media);
    }

    private function createFileVersion(File $file, $title, $version): void
    {
        $fileVersion = new FileVersion();
        $fileVersion->setName($title . '.png');
        $fileVersion->setVersion($version);
        $fileVersion->setSize(0);
        $fileVersion->setFile($file);

        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setTitle($title);
        $fileVersionMeta->setLocale('en');
        $fileVersionMeta->setFileVersion($fileVersion);

        $this->em->persist($fileVersion);
        $this->em->persist($fileVersionMeta);
    }
}
