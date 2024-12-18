<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaPreviewControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var MediaType
     */
    private $imageType;

    /**
     * @var MediaType
     */
    private $videoType;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();

        $metadata = $this->em->getClassMetaData(CollectionType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        // to be sure that the system collections will rebuild after purge database
        $systemCollectionCache = $this->getContainer()->get('sulu_media_test.system_collections.cache');
        $systemCollectionCache->invalidate();

        $this->collection = new Collection();

        $collectionType1 = new CollectionType();
        $collectionType1->setId(1);
        $collectionType1->setName('Default Collection Type');
        $collectionType1->setDescription('Default Collection Type');

        // This CollectionType just must be created because the SystemCollectionManager requires one with ID 2 to exist
        $collectionType2 = new CollectionType();
        $collectionType2->setId(2);
        $collectionType2->setName('Default Collection Type');
        $collectionType2->setDescription('Default Collection Type');

        $this->collection->setType($collectionType1);

        // Collection Meta 1
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($this->collection);

        $this->collection->addMeta($collectionMeta);

        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->videoType = new MediaType();
        $this->videoType->setName('video');
        $this->videoType->setDescription('This is a video');

        $this->em->persist($this->collection);
        $this->em->persist($this->imageType);
        $this->em->persist($this->videoType);
        $this->em->persist($collectionType1);
        $this->em->persist($collectionType2);
        $this->em->persist($collectionMeta);

        $this->em->flush();
    }

    public function testPost(): void
    {
        $media = $this->createMedia('photo');
        $preview = new UploadedFile($this->getImagePath(), 'preview.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '/preview?locale=en',
            [],
            ['previewImage' => $preview]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals('photo', $response->title);
        $this->assertStringContainsString('preview.jpg?v=1-0', $response->thumbnails->{'sulu-400x400'});

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '/preview?locale=en',
            [],
            ['previewImage' => $preview]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals('photo', $response->title);
        $this->assertStringContainsString('preview.jpg?v=2-0', $response->thumbnails->{'sulu-400x400'});
    }

    public function testDelete(): void
    {
        $preview = $this->createMedia('preview');
        $media = $this->createMedia('photo', 'en-gb', 'image', $preview);
        $mediaId = $media->getId();

        $this->assertEquals($preview, $media->getPreviewImage());

        $this->client->jsonRequest('DELETE', '/api/media/' . $mediaId . '/preview?locale=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals('photo', $response->title);
        $this->assertStringContainsString('photo.jpg?v=1-0', $response->thumbnails->{'sulu-400x400'});
    }

    private function createMedia($name, $locale = 'en-gb', $type = 'image', ?Media $previewMedia = null)
    {
        $media = new Media();
        $media->setPreviewImage($previewMedia);

        if ('image' === $type) {
            $media->setType($this->imageType);
            $extension = 'jpeg';
            $mimeType = 'image/jpg';
        } elseif ('video' === $type) {
            $media->setType($this->videoType);
            $extension = 'mp4';
            $mimeType = 'video/mp4';
        }

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.' . $extension);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $fileVersion->setStorageOptions(['segment' => '1', 'fileName' => $name . '.' . $extension]);
        $storagePath = $this->getStoragePath();

        if (!\file_exists($storagePath . '/1')) {
            \mkdir($storagePath . '/1', 0777, true);
        }
        \copy($this->getImagePath(), $storagePath . '/1/' . $name . '.' . $extension);

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setTitle($name);
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);
        $media->setCollection($this->collection);

        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($fileVersion);

        $this->em->flush();

        return $media;
    }

    private function getImagePath(): string
    {
        return __DIR__ . '/../../Fixtures/files/photo.jpeg';
    }

    private function getStoragePath(): string
    {
        return $this->getContainer()->getParameter('kernel.project_dir') . '/var/uploads';
    }
}
