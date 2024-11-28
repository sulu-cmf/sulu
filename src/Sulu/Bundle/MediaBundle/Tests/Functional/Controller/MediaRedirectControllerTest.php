<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
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
use Symfony\Component\HttpFoundation\Response;

class MediaRedirectControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CollectionType
     */
    private $collectionType;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var CollectionMeta
     */
    private $collectionMeta;

    /**
     * @var MediaType
     */
    private $documentType;

    /**
     * @var MediaType
     */
    private $imageType;

    /**
     * @var MediaType
     */
    private $videoType;

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var CategoryInterface
     */
    private $category2;

    /**
     * @var string
     */
    protected $mediaDefaultTitle = 'photo';

    /**
     * @var string
     */
    protected $mediaDefaultDescription = 'description';

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->cleanImage();
        $this->setUpCollection();
        $this->setUpCategory();
        $this->setUpMedia();
    }

    protected function cleanImage()
    {
        if ($this->getContainer()) {
            $configPath = $this->getContainer()->getParameter('kernel.project_dir') . '/var/uploads';
            $this->recursiveRemoveDirectory($configPath);

            $cachePath = $this->getContainer()->getParameter('sulu_media.format_cache.path');
            $this->recursiveRemoveDirectory($cachePath);
        }
    }

    public function recursiveRemoveDirectory($directory, $counter = 0): void
    {
        foreach (\glob($directory . '/*') as $file) {
            if (\is_dir($file)) {
                $this->recursiveRemoveDirectory($file, $counter + 1);
            } elseif (\file_exists($file)) {
                \unlink($file);
            }
        }

        if (0 != $counter) {
            \rmdir($directory);
        }
    }

    /**
     * set up two categories.
     */
    private function setUpCategory(): void
    {
        /* First Category
        -------------------------------------*/
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey('first-category-key');
        $category->setDefaultLocale('en');

        $this->category = $category;

        // name for first category
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // meta for first category
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        /* Second Category
        -------------------------------------*/
        $category2 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category2->setKey('second-category-key');
        $category2->setDefaultLocale('de');

        $this->category2 = $category2;

        // name for second category
        $categoryTrans2 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // meta for second category
        $categoryMeta2 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Description of second Category');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        $this->em->persist($category2);
        $this->em->persist($category);

        $this->em->flush();
    }

    protected function setUpMedia()
    {
        // Create Media Type
        $this->documentType = new MediaType();
        $this->documentType->setName('document');
        $this->documentType->setDescription('This is a document');

        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->videoType = new MediaType();
        $this->videoType->setName('video');
        $this->videoType->setDescription('This is a video');

        $tagRepository = $this->getContainer()->get('sulu.repository.tag');

        // create some tags
        $tag1 = $tagRepository->createNew();
        $tag1->setName('Tag 1');

        $tag2 = $tagRepository->createNew();
        $tag2->setName('Tag 2');

        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($this->documentType);
        $this->em->persist($this->imageType);
        $this->em->persist($this->videoType);

        $this->em->flush();
    }

    protected function createMedia($name, $locale = 'en-gb')
    {
        $media = new Media();
        $media->setType($this->imageType);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->addCategory($this->category);
        $fileVersion->addCategory($this->category2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $fileVersion->setStorageOptions(['segment' => 1, 'fileName' => $name . '.jpeg']);
        if (!\file_exists(__DIR__ . '/../../uploads/media/1')) {
            \mkdir(__DIR__ . '/../../uploads/media/1', 0777, true);
        }
        \copy($this->getImagePath(), __DIR__ . '/../../uploads/media/1/' . $name . '.jpeg');

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setTitle($name);
        $fileVersionMeta->setDescription($this->mediaDefaultDescription);
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

    protected function setUpCollection()
    {
        $this->collection = new Collection();
        $style = [
            'type' => 'circle', 'color' => '#ffcc00',
        ];

        $this->collection->setStyle(\json_encode($style) ?: null);

        // Create Collection Type
        $this->collectionType = new CollectionType();
        $this->collectionType->setName('Default Collection Type');
        $this->collectionType->setDescription('Default Collection Type');

        $this->collection->setType($this->collectionType);

        // Collection Meta 1
        $this->collectionMeta = new CollectionMeta();
        $this->collectionMeta->setTitle('Test Collection');
        $this->collectionMeta->setDescription('This Description is only for testing');
        $this->collectionMeta->setLocale('en-gb');
        $this->collectionMeta->setCollection($this->collection);

        $this->collection->addMeta($this->collectionMeta);

        // Collection Meta 2
        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setTitle('Test Kollektion');
        $collectionMeta2->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setCollection($this->collection);

        $this->collection->addMeta($collectionMeta2);

        $this->em->persist($this->collection);
        $this->em->persist($this->collectionType);
        $this->em->persist($this->collectionMeta);
        $this->em->persist($collectionMeta2);
    }

    public function test404NotFound(): void
    {
        $this->client->jsonRequest('GET', '/redirect/media/1?locale=en-gb');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test redirect to original.
     */
    public function testRedirect(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest('GET', '/redirect/media/' . $media->getId() . '?locale=en-gb');

        $this->assertEquals(
            '/media/' . $media->getId() . '/download/photo.jpeg?v=1',
            $this->client->getResponse()->headers->get('location')
        );
    }

    /**
     * Test redirect to format.
     */
    public function testRedirectFormat(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest('GET', '/redirect/media/' . $media->getId() . '?locale=en-gb&format=sulu-50x50');
        $location = $this->client->getResponse()->headers->get('location');
        $this->assertNotNull($location);

        $this->assertMatchesRegularExpression(
            '/\/uploads\/media\/sulu-50x50\/\d{2}\/' . $media->getId() . '-photo.jpg\?v=1-0/',
            $location
        );
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../Fixtures/files/photo.jpeg';
    }
}
