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

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CollectionType
     */
    private $systemCollectionType;

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
     * @var MediaType
     */
    private $audioType;

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var CategoryInterface
     */
    private $category2;

    /**
     * @var TagInterface
     */
    private $tag1;

    /**
     * @var TagInterface
     */
    private $tag2;

    /**
     * @var string
     */
    protected $mediaDefaultTitle = 'photo';

    /**
     * @var string
     */
    protected $mediaDefaultDescription = 'description';

    /**
     * @var string
     */
    protected $mediaDefaultCopyright = 'copyright';

    /**
     * @var string
     */
    protected $mediaDefaultCredits = 'credits';

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

    protected function cleanImage(): void
    {
        if ($this->getContainer()) {
            $configPath = $this->getContainer()->getParameter('sulu_media.media.storage.local.path');
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

    protected function setUpMedia(): void
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

        $this->audioType = new MediaType();
        $this->audioType->setName('audio');
        $this->audioType->setDescription('This is a video');

        $tagRepository = $this->getContainer()->get('sulu.repository.tag');

        // create some tags
        $this->tag1 = $tagRepository->createNew();
        $this->tag1->setName('Tag 1');

        $this->tag2 = $tagRepository->createNew();
        $this->tag2->setName('Tag 2');

        $this->em->persist($this->tag1);
        $this->em->persist($this->tag2);
        $this->em->persist($this->documentType);
        $this->em->persist($this->audioType);
        $this->em->persist($this->imageType);
        $this->em->persist($this->videoType);

        $this->em->flush();
    }

    protected function createMedia($name, $locale = 'en-gb', $type = 'image', ?Media $previewMedia = null)
    {
        $media = new Media();
        $media->setPreviewImage($previewMedia);

        if ('image' === $type) {
            $media->setType($this->imageType);
            $extension = 'jpeg';
            $mimeType = 'image/jpg';
        } elseif ('audio' === $type) {
            $media->setType($this->audioType);
            $extension = 'mp3';
            $mimeType = 'audio/mp3';
        } elseif ('video' === $type) {
            $media->setType($this->videoType);
            $extension = 'mp4';
            $mimeType = 'video/mp4';
        } else {
            $media->setType($this->documentType);
            $extension = 'txt';
            $mimeType = 'text/plain';
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
        $fileVersion->addCategory($this->category);
        $fileVersion->addCategory($this->category2);
        $fileVersion->addTag($this->tag1);
        $fileVersion->addTag($this->tag2);
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
        $fileVersionMeta->setDescription($this->mediaDefaultDescription);
        $fileVersionMeta->setCredits($this->mediaDefaultCredits);
        $fileVersionMeta->setCopyright($this->mediaDefaultCopyright);
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        // create format options
        $formatOptions = new FormatOptions();
        $formatOptions->setFileVersion($fileVersion);
        $formatOptions->setFormatKey('format-key-1');
        $formatOptions->setCropX(50);
        $formatOptions->setCropY(50);
        $formatOptions->setCropWidth(100);
        $formatOptions->setCropHeight(100);

        $fileVersion->addFormatOptions($formatOptions);

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

    protected function setUpCollection(): void
    {
        $this->collection = new Collection();
        $style = [
            'type' => 'circle', 'color' => '#ffcc00',
        ];

        $this->collection->setStyle(\json_encode($style) ?: null);

        // Create Collection Type
        $collectionTypeFixtures = new LoadCollectionTypes();
        $collectionTypeFixtures->load($this->getEntityManager());

        $this->collection->setType($this->getEntityManager()->getReference(CollectionType::class, 1));

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
        $this->em->persist($this->collectionMeta);
        $this->em->persist($collectionMeta2);
        $this->collection->setDefaultMeta($this->collectionMeta);
    }

    /**
     * Test Media DownloadCounter.
     */
    public function test404ResponseHeader(): void
    {
        $this->client->request(
            'GET',
            '/uploads/media/50x50/1/0-photo.jpg'
        );
        $this->assertFalse($this->client->getResponse()->isCacheable());
        $expiresDate = new \DateTime($this->client->getResponse()->headers->get('Expires'));
        $expiresDate->modify('+1 second');
        $this->assertGreaterThanOrEqual(new \DateTime(), $expiresDate);
    }

    /**
     * Test Header dispositionType attachment.
     */
    public function testDownloadHeaderAttachment(): void
    {
        $media = $this->createMedia('photo');

        \ob_start();
        $this->client->request(
            'GET',
            '/media/' . $media->getId() . '/download/photo.jpeg'
        );
        \ob_end_clean();
        $this->assertEquals(
            'attachment; filename=photo.jpeg',
            \str_replace('"', '', $this->client->getResponse()->headers->get('Content-Disposition'))
        );
    }

    /**
     * Test Header dispositionType inline.
     */
    public function testDownloadHeaderInline(): void
    {
        $media = $this->createMedia('photo');

        \ob_start();
        $this->client->request(
            'GET',
            '/media/' . $media->getId() . '/download/photo.jpeg?inline=1'
        );
        \ob_end_clean();
        $this->assertEquals(
            'inline; filename=photo.jpeg',
            \str_replace('"', '', $this->client->getResponse()->headers->get('Content-Disposition'))
        );
    }

    /**
     * Test Header dispositionType umlauts in name.
     */
    public function testDownloadHeaderUmlauts(): void
    {
        $media = $this->createMedia('wöchentlich');

        \ob_start();
        $this->client->request(
            'GET',
            '/media/' . $media->getId() . '/download/wöchentlich.jpeg?inline=1'
        );
        \ob_end_clean();
        $this->assertEquals(
            'inline; filename=woechentlich.jpeg; filename*=utf-8\'\'w%C3%B6chentlich.jpeg',
            \str_replace('"', '', $this->client->getResponse()->headers->get('Content-Disposition'))
        );
    }

    /**
     * Test Media GET by ID.
     */
    public function testGetById(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'GET',
            '/api/media/' . $media->getId() . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($media->getId(), $response['id']);
        $this->assertNotNull($response['type']['id']);
        $this->assertEquals('image', $response['type']['name']);
        $this->assertEquals('en-gb', $response['locale']);
        $this->assertNull($response['fallbackLocale']);
        $this->assertEquals('photo.jpeg', $response['name']);
        $this->assertEquals($this->mediaDefaultTitle, $response['title']);
        $this->assertEquals('2', $response['downloadCounter']);
        $this->assertEquals($this->mediaDefaultDescription, $response['description']);
        $this->assertNotEmpty($response['url']);
        $this->assertNotEmpty($response['adminUrl']);
        $this->assertNotEmpty($response['thumbnails']);
        $this->assertNull($response['previewImageId']);

        $this->assertContains($this->category->getId(), $response['categories']);
        $this->assertContains($this->category2->getId(), $response['categories']);
    }

    /**
     * Test Media GET by ID, without passing a locale.
     */
    public function testGetByIdWithNoLocale(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'GET',
            '/api/media/' . $media->getId()
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testGetByIdWithFallback(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '?locale=de');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($media->getId(), $response['id']);
        $this->assertEquals('de', $response['locale']);
        $this->assertEquals('en-gb', $response['fallbackLocale']);
    }

    public function testCget(): void
    {
        $this->createMedia('photo');
        $this->createMedia('photo2');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
    }

    public function testCgetAdminUrl(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(
            '/admin/media/' . $media->getId() . '/download/photo.jpeg?v=1',
            $response['_embedded']['media'][0]['adminUrl']
        );
    }

    public function testCgetWithPreview(): void
    {
        $preview1 = $this->createMedia('preview-image-1');
        $preview2 = $this->createMedia('preview-image-2');
        $this->createMedia('photo1', 'en-gb', 'image', $preview1);
        $this->createMedia('photo2', 'en-gb', 'image', $preview2);

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&sortBy=name'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertStringContainsString('photo1', $response->_embedded->media[0]->url);
        $this->assertStringContainsString(
            $preview1->getId() . '-preview-image-1.jpg',
            $response->_embedded->media[0]->thumbnails->{'sulu-400x400'}
        );

        $this->assertStringContainsString('photo2', $response->_embedded->media[1]->url);
        $this->assertStringContainsString(
            $preview2->getId() . '-preview-image-2.jpg',
            $response->_embedded->media[1]->thumbnails->{'sulu-400x400'}
        );

        $this->assertStringContainsString('preview-image-1', $response->_embedded->media[2]->url);
        $this->assertStringContainsString(
            $preview1->getId() . '-preview-image-1.jpg',
            $response->_embedded->media[2]->thumbnails->{'sulu-400x400'}
        );

        $this->assertStringContainsString('preview-image-2', $response->_embedded->media[3]->url);
        $this->assertStringContainsString(
            $preview2->getId() . '-preview-image-2.jpg',
            $response->_embedded->media[3]->thumbnails->{'sulu-400x400'}
        );
    }

    public function testCgetExcludedIds(): void
    {
        $media = $this->createMedia('photo');
        $this->createMedia('photo1');
        $media2 = $this->createMedia('photo2');
        $this->createMedia('photo3');

        $excludedIds = \implode(',', [$media->getId(), $media2->getId()]);

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&excludedIds=' . $excludedIds
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
    }

    public function testCgetWithNoLocale(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/media'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testCgetCollection(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    public function testcGetCollectionTypes(): void
    {
        $media = $this->createMedia('photo');
        $document = $this->createMedia('document', 'en-gb', 'document');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId() . '&types=image'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    public function testcGetCollectionTypesNotExisting(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId() . '&types=audio'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(0, $response->total);
    }

    public function testcGetCollectionTypesMultiple(): void
    {
        $media = $this->createMedia('photo');
        $audio = $this->createMedia('audio', 'en-gb', 'audio');
        $document = $this->createMedia('document', 'en-gb', 'document');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId() . '&types=image,audio&sortBy=id'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
        $this->assertEquals($audio->getId(), $response->_embedded->media[1]->id);
        $this->assertEquals('audio.mp3', $response->_embedded->media[1]->name);
    }

    public function testcGetFallbacks(): void
    {
        $mediaEN = $this->createMedia('test-en', 'en');
        $mediaDE = $this->createMedia('test-de', 'de');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=de'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertNotEmpty($response);

        $medias = \array_map(
            function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'title' => $item->title,
                    'locale' => $item->locale,
                    'ghostLocale' => $item->ghostLocale ?? null,
                ];
            },
            $response->_embedded->media
        );

        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $medias);
        $this->assertContains(
            [
                'id' => $mediaEN->getId(),
                'name' => 'test-en.jpeg',
                'title' => 'test-en',
                'locale' => 'en',
                'ghostLocale' => 'en',
            ],
            $medias
        );
        $this->assertContains(
            [
                'id' => $mediaDE->getId(),
                'name' => 'test-de.jpeg',
                'title' => 'test-de',
                'locale' => 'de',
                'ghostLocale' => null,
            ],
            $medias
        );
    }

    public function testcGetIds(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&ids=' . $media->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    public function testcGetMultipleIds(): void
    {
        $media1 = $this->createMedia('photo1');
        $media2 = $this->createMedia('photo2');
        $this->createMedia('photo3');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&ids=' . $media2->getId() . ',' . $media1->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $medias = \array_map(
            function($item) {
                return ['id' => $item->id, 'name' => $item->name];
            },
            $response->_embedded->media
        );

        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $medias);

        $this->assertEquals(['id' => $media1->getId(), 'name' => 'photo1.jpeg'], $medias[1]);
        $this->assertEquals(['id' => $media2->getId(), 'name' => 'photo2.jpeg'], $medias[0]);
    }

    public function testCgetSearch(): void
    {
        $this->createMedia('photo1');
        $this->createMedia('photo2');
        $this->createMedia('picture3');

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&searchFields=title&search=photo%2A'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['media']);

        $titles = \array_map(
            function($media) {
                return $media['name'];
            },
            $response['_embedded']['media']
        );

        $this->assertContains('photo2.jpeg', $titles);
        $this->assertContains('photo1.jpeg', $titles);
    }

    public function testCgetFilterByTag(): void
    {
        $media1 = $this->createMedia('photo1');
        $media1->getFiles()[0]->getLatestFileVersion()->removeTags();

        $media2 = $this->createMedia('photo2');
        $media2->getFiles()[0]->getLatestFileVersion()->removeTags();
        $media2->getFiles()[0]->getLatestFileVersion()->addTag($this->tag1);

        $media3 = $this->createMedia('photo3');
        $media3->getFiles()[0]->getLatestFileVersion()->removeTags();
        $media3->getFiles()[0]->getLatestFileVersion()->addTag($this->tag2);

        $media4 = $this->createMedia('photo4');
        $media4->getFiles()[0]->getLatestFileVersion()->removeTags();
        $media4->getFiles()[0]->getLatestFileVersion()->addTag($this->tag1);
        $media4->getFiles()[0]->getLatestFileVersion()->addTag($this->tag2);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&filter[tagId]=' . $this->tag1->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['media']);

        $titles = \array_map(
            function($media) {
                return $media['name'];
            },
            $response['_embedded']['media']
        );

        $this->assertContains('photo2.jpeg', $titles);
        $this->assertContains('photo4.jpeg', $titles);
    }

    public function testcGetNotExistingIds(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/media?locale=en-gb&ids=1232,3123,1234'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(0, $response->total);
    }

    public function testGetByIdNotExisting(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/media/11230?locale=en-gb'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPost(): void
    {
        /** @var TargetGroupRepositoryInterface $targetGroupRepository */
        $targetGroupRepository = $this->getContainer()->get('sulu.repository.target_group');

        /** @var TargetGroup $targetGroup1 */
        $targetGroup1 = $targetGroupRepository->createNew();
        $targetGroup1->setTitle('Target Group 1');
        $targetGroup1->setPriority(1);
        /** @var TargetGroup $targetGroup2 */
        $targetGroup2 = $targetGroupRepository->createNew();
        $targetGroup2->setTitle('Target Group 2');
        $targetGroup2->setPriority(1);

        $this->getEntityManager()->persist($targetGroup1);
        $this->getEntityManager()->persist($targetGroup2);
        $this->getEntityManager()->flush();

        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media',
            [
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'New Image Title',
                'description' => 'New Image Description',
                'copyright' => 'My copyright',
                'credits' => 'My credits',
                'contentLanguages' => [
                    'en-gb',
                ],
                'focusPointX' => 0,
                'focusPointY' => 0,
                'publishLanguages' => [
                    'en-gb',
                    'en-au',
                    'en',
                    'de',
                ],
                'targetGroups' => [
                    $targetGroup1->getId(),
                    $targetGroup2->getId(),
                ],
                'categories' => [
                    $this->category->getId(),
                    $this->category2->getId(),
                ],
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, \count($this->client->getRequest()->files->all()));

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertNotNull($response->id);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals('New Image Title', $response->title);
        $this->assertEquals('New Image Description', $response->description);
        $this->assertEquals('My copyright', $response->copyright);
        $this->assertEquals('My credits', $response->credits);
        $this->assertEquals(0, $response->focusPointX);
        $this->assertEquals(0, $response->focusPointY);
        $this->assertNotEmpty($response->url);
        $this->assertNotEmpty($response->thumbnails);
        $this->assertEquals(
            [
                'en-gb',
            ],
            $response->contentLanguages
        );
        $this->assertEquals(
            [
                'en-gb',
                'en-au',
                'en',
                'de',
            ],
            $response->publishLanguages
        );

        $this->assertContains($this->category->getId(), $response->categories);
        $this->assertContains($this->category2->getId(), $response->categories);

        $targetGroups = [
            $response->targetGroups[0],
            $response->targetGroups[1],
        ];

        $this->assertContains($targetGroup1->getId(), $targetGroups);
        $this->assertContains($targetGroup2->getId(), $targetGroups);
    }

    public function testPostWithoutDetails(): void
    {
        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media',
            [
                'locale' => 'en',
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, \count($this->client->getRequest()->files->all()));

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals($this->mediaDefaultTitle, $response->title);

        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertNotNull($response->id);
    }

    #[\PHPUnit\Framework\Attributes\Group('postWithoutDetails')]
    public function testPostWithoutExtension(): void
    {
        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media',
            [
                'locale' => 'en',
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, \count($this->client->getRequest()->files->all()));

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals($this->mediaDefaultTitle, $response->title);

        $this->assertEquals('photo', $response->name);
        $this->assertNotNull($response->id);
    }

    public function testPostWithSmallFile(): void
    {
        $filePath = $this->getFilePath();
        $this->assertTrue(\file_exists($filePath));
        $photo = new UploadedFile($filePath, 'small.txt', 'text/plain');

        $this->client->request(
            'POST',
            '/api/media',
            [
                'locale' => 'en',
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, \count($this->client->getRequest()->files->all()));

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('small', $response->title);

        $this->assertEquals('small.txt', $response->name);
        $this->assertNotNull($response->id);
    }

    public function testFileVersionUpdate(): void
    {
        $media = $this->createMedia('photo');

        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '?action=new-version',
            [
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'New Image Title',
                'description' => 'New Image Description',
                'copyright' => 'My copyright',
                'credits' => 'My credits',
                'contentLanguages' => [
                    'en-gb',
                ],
                'publishLanguages' => [
                    'en-gb',
                    'en-au',
                    'en',
                    'de',
                ],
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, \count($this->client->getRequest()->files->all()));

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(2, $response->version);
        $this->assertCount(2, (array) $response->versions);
        $this->assertNotEquals($response->versions->{'2'}->created, $response->versions->{'1'}->created);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('New Image Title', $response->title);
        $this->assertEquals('New Image Description', $response->description);
        $this->assertEquals('My copyright', $response->copyright);
        $this->assertEquals('My credits', $response->credits);
        $this->assertEquals(
            [
                'en-gb',
            ],
            $response->contentLanguages
        );
        $this->assertEquals(
            [
                'en-gb',
                'en-au',
                'en',
                'de',
            ],
            $response->publishLanguages
        );
    }

    public function testFileVersionDelete(): void
    {
        $media = $this->createMedia('photo');
        $mediaId = $media->getId();

        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en&action=new-version',
            [],
            [
                'fileVersion' => $photo,
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, (array) $response->versions);

        $this->client->jsonRequest(
            'DELETE',
            '/api/media/' . $mediaId . '/versions/1?locale=en'
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/media/' . $mediaId . '?locale=en'
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertCount(1, (array) $response->versions);
        $this->assertEquals(2, $response->version);
    }

    public function testFileVersionDeleteCurrentShould400(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'DELETE',
            '/api/media/' . $media->getId() . '/versions/1?locale=en'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testFileVersionDeleteNotExistShould400(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'DELETE',
            '/api/media/' . $media->getId() . '/versions/2?locale=en'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testFileVersionDeleteActiveShould400(): void
    {
        $media = $this->createMedia('photo');

        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en&action=new-version',
            [],
            [
                'fileVersion' => $photo,
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, (array) $response->versions);

        $this->client->jsonRequest(
            'DELETE',
            '/api/media/' . $media->getId() . '/versions/2?locale=en'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPutRemovingMetadata(): void
    {
        $media = $this->createMedia('image', 'de');

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '?locale=de',
            [
                'description' => null,
                'copyright' => null,
                'credits' => null,
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(null, $response['description']);
        $this->assertSame(null, $response['copyright']);
        $this->assertSame(null, $response['credits']);
    }

    public function testPutRemovingTargetGroups(): void
    {
        /** @var TargetGroupRepositoryInterface $targetGroupRepository */
        $targetGroupRepository = $this->getContainer()->get('sulu.repository.target_group');

        /** @var TargetGroup $targetGroup1 */
        $targetGroup1 = $targetGroupRepository->createNew();
        $targetGroup1->setTitle('Target Group 1');
        $targetGroup1->setPriority(1);

        $this->em->persist($targetGroup1);
        $this->em->flush();

        $media = $this->createMedia('photo');
        $file = $media->getFiles()[0];
        $file->getFileVersion($file->getVersion())->addTargetGroup($targetGroup1);

        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '?locale=en',
            [
                'targetGroups' => [],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals([], $response->targetGroups);
    }

    public function testPutWithoutFile(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId(),
            [
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'Update Title',
                'description' => 'Update Description',
                'copyright' => 'My copyright',
                'credits' => 'My credits',
                'focusPointX' => 1,
                'focusPointY' => 2,
                'contentLanguages' => [
                    'en-gb',
                ],
                'tags' => ['Tag 1', 'Tag 2'],
                'publishLanguages' => [
                    'en-gb',
                    'en-au',
                    'en',
                    'de',
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(1, $response->version);
        $this->assertCount(1, (array) $response->versions);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('Update Title', $response->title);
        $this->assertEquals('Update Description', $response->description);
        $this->assertEquals('My copyright', $response->copyright);
        $this->assertEquals('My credits', $response->credits);
        $this->assertEquals(1, $response->focusPointX);
        $this->assertEquals(2, $response->focusPointY);
        $this->assertNotEmpty($response->url);
        $this->assertEquals(['Tag 1', 'Tag 2'], $response->tags);

        $this->assertNotEmpty($response->thumbnails);
        $this->assertEquals(
            [
                'en-gb',
            ],
            $response->contentLanguages
        );
        $this->assertEquals(
            [
                'en-gb',
                'en-au',
                'en',
                'de',
            ],
            $response->publishLanguages
        );
    }

    public function testTagRemove(): void
    {
        $media = $this->createMedia('photo');
        $mediaId = $media->getId();

        // Check Tag Remove
        $this->getEntityManager()->remove($this->tag1);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/media/' . $mediaId . '?locale=en'
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(['Tag 2'], $response->tags);
    }

    public function testFileVersionUpdateWithoutDetails(): void
    {
        $media = $this->createMedia('photo');

        $imagePath = $this->getImagePath();
        $this->assertTrue(\file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg');

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en&action=new-version',
            [
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, \count($this->client->getRequest()->files->all()));

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals($this->mediaDefaultTitle, $response->title);
        $this->assertEquals($this->mediaDefaultDescription, $response->description);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(2, $response->version);
        $this->assertCount(2, (array) $response->versions);
        $this->assertNotEmpty($response->url);
        $this->assertNotEmpty($response->thumbnails);
    }

    public function testDeleteById(): void
    {
        $media = $this->createMedia('photo');
        $mediaId = $media->getId();
        $this->assertFileExists($this->getStoragePath() . '/1/photo.jpeg');

        $this->client->jsonRequest('DELETE', '/api/media/' . $mediaId);
        $this->assertNotNull($this->client->getResponse()->getStatusCode());

        $this->client->jsonRequest(
            'GET',
            '/api/media/' . $mediaId . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertObjectHasProperty('message', $response);

        $this->assertFalse(\file_exists($this->getStoragePath() . '/1/photo.jpeg'));
    }

    public function testDeleteByIdNotExisting(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest('DELETE', '/api/media/404');
        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/media?locale=en-gb');
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    public function testDownloadCounter(): void
    {
        $media = $this->createMedia('photo');

        \ob_start();
        $this->client->jsonRequest(
            'GET',
            '/media/' . $media->getId() . '/download/photo.jpeg'
        );
        \ob_end_clean();

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/media/' . $media->getId() . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals('image', $response->type->name);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals($this->mediaDefaultTitle, $response->title);
        $this->assertEquals('3', $response->downloadCounter);
        $this->assertEquals($this->mediaDefaultDescription, $response->description);
    }

    public function testMove(): void
    {
        $destCollection = new Collection();
        $style = [
            'type' => 'circle',
            'color' => '#ffcc00',
        ];

        $destCollection->setStyle(\json_encode($style) ?: null);
        $destCollection->setType($this->getEntityManager()->getReference(CollectionType::class, 1));
        $destCollection->addMeta($this->collectionMeta);

        $this->em->persist($destCollection);
        $this->em->flush();

        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en-gb&action=move&destination=' . $destCollection->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals($destCollection->getId(), $response['collection']);
        $this->assertEquals($this->mediaDefaultTitle, $response['title']);
    }

    public function testMoveWithNoLocale(): void
    {
        $destCollection = new Collection();
        $style = [
            'type' => 'circle',
            'color' => '#ffcc00',
        ];

        $destCollection->setStyle(\json_encode($style) ?: null);
        $destCollection->setType($this->getEntityManager()->getReference(CollectionType::class, 1));
        $destCollection->addMeta($this->collectionMeta);

        $this->em->persist($destCollection);
        $this->em->flush();

        $media = $this->createMedia('photo');

        $this->client->jsonRequest(
            'POST',
            '/api/media/' . $media->getId() . '?action=move&destination=' . $destCollection->getId()
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testMoveNonExistingCollection(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest('POST', '/api/media/' . $media->getId() . '?action=move&destination=404');

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testMoveNonExistingMedia(): void
    {
        $this->client->jsonRequest('POST', '/api/media/404?locale=en-gb&action=move&destination=' . $this->collection->getId());

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testMoveNonExistingAction(): void
    {
        $media = $this->createMedia('photo');

        $this->client->jsonRequest('POST', '/api/media/' . $media->getId() . '?action=test');

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    private function getStoragePath()
    {
        return $this->getContainer()->getParameter('sulu_media.media.storage.local.path');
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../Fixtures/files/photo.jpeg';
    }

    /**
     * @return string
     */
    private function getFilePath()
    {
        return __DIR__ . '/../../Fixtures/files/small.txt';
    }
}
