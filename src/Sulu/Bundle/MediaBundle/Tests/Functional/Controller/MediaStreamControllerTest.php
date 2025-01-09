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

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaStreamControllerTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        $this->purgeDatabase();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    public function testDownloadAction(): void
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        $this->client->request('GET', $media->getUrl());
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testDownloadActionNotMatchingFileName(): void
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'test.jpg');

        $this->client->request('GET', \str_replace('test.jpg', 'other.jpg', $media->getUrl()));
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testNotExistVersionDownloadAction(): void
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        $this->client->request('GET', \str_replace('v=1', 'v=99', $media->getUrl()));
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testOldExistVersionDownloadAction(): void
    {
        $filePath1 = $this->createMediaFile('test-1.jpg');
        $filePath2 = $this->createMediaFile('test-2.jpg');
        $oldMedia = $this->createMedia($filePath1, 'file-without-extension');
        $newMedia = $this->createMediaVersion($oldMedia->getId(), $filePath2, 'new-file-without-extension');

        $this->client->request('GET', $oldMedia->getUrl());
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertEquals(
            \sprintf(
                '<%s>; rel="canonical"',
                $newMedia->getUrl()
            ),
            $response->headers->get('Link')
        );
        $this->assertEquals(
            'noindex, follow',
            $response->headers->get('X-Robots-Tag')
        );
        $this->assertSame('attachment; filename=test-1.jpg', $response->headers->get('Content-Disposition'));

        $this->client->request('GET', $newMedia->getUrl());
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('attachment; filename=test-2.jpg', $response->headers->get('Content-Disposition'));
    }

    public function testDownloadWithoutExtensionAction(): void
    {
        $filePath = $this->createMediaFile('file-without-extension');
        $media = $this->createMedia($filePath, 'File without Extension');

        $this->client->request('GET', $media->getUrl());
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testDownloadWithDotInName(): void
    {
        $filePath = $this->createMediaFile('fitness-seasons.agency--C-&-C--Rodach,-Johannes');
        $media = $this->createMedia($filePath, 'fitness-seasons.agency--C-&-C--Rodach,-Johannes');

        $this->client->request('GET', $media->getUrl());
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        // >= Symfony 5.2 serializes filename to *.jpg instead of *.jpeg
        $this->assertContains(
            \str_replace('"', '', $response->headers->get('Content-Disposition')),
            [
                'attachment; filename=fitness-seasons.jpg; filename*=utf-8\'\'fitness-seasons.agency--C-%26-C--Rodach%2C-Johannes',
                'attachment; filename=fitness-seasons.jpeg; filename*=utf-8\'\'fitness-seasons.agency--C-%26-C--Rodach%2C-Johannes',
            ]
        );
    }

    public function testGetImageActionForNonExistingMedia(): void
    {
        $this->client->request('GET', '/uploads/media/sulu-400x400/01/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetImageAction(): void
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'Test jpg');

        $this->client->request('GET', $media->getFormats()['small-inset']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('image/jpeg', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('immutable, max-age=31536000, public', $this->client->getResponse()->headers->get('Cache-Control'));
        $this->assertNull($this->client->getResponse()->headers->get('Expires'));
        $this->assertNull($this->client->getResponse()->headers->get('Pragma'));

        $this->client->request('GET', $media->getFormats()['small-inset.gif']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('image/gif', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('immutable, max-age=31536000, public', $this->client->getResponse()->headers->get('Cache-Control'));
        $this->assertNull($this->client->getResponse()->headers->get('Expires'));
        $this->assertNull($this->client->getResponse()->headers->get('Pragma'));
    }

    public function testGetImageActionSvg(): void
    {
        $filePath = $this->createMediaFile('test.svg', 'sulu.svg');
        $media = $this->createMedia($filePath, 'Test svg');

        $this->client->request('GET', $media->getFormats()['small-inset']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('image/svg+xml', $this->client->getResponse()->headers->get('Content-Type'));

        $this->client->request('GET', $media->getFormats()['small-inset.svg']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('image/svg+xml', $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testGetImageActionSvgAsJpg(): void
    {
        if (!\class_exists(\Imagick::class)) {
            $this->markTestSkipped('Imagick pecl extension is not installed.');

            return;
        }

        $filePath = $this->createMediaFile('test.svg', 'sulu.svg');
        $media = $this->createMedia($filePath, 'Test svg');

        $this->client->request('GET', $media->getFormats()['small-inset.jpg']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('image/jpeg', $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testDownloadActionForNonExistingMedia(): void
    {
        $this->client->request('GET', '/media/999/download/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    private function createUploadedFile(string $path): UploadedFile
    {
        /** @var string $mimeType */
        $mimeType = \mime_content_type($path);

        return new UploadedFile($path, \basename($path), $mimeType);
    }

    private function createCollection(string $title = 'Test'): int
    {
        $collection = $this->getCollectionManager()->save(
            [
                'title' => $title,
                'locale' => 'en',
                'type' => ['id' => 1],
            ],
            1
        );

        return $collection->getId();
    }

    private function createMedia(string $path, string $title): Media
    {
        return $this->getMediaManager()->save(
            $this->createUploadedFile($path),
            [
                'title' => $title,
                'collection' => $this->createCollection(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function createMediaVersion(int $id, string $path, string $title): Media
    {
        return $this->getMediaManager()->save(
            $this->createUploadedFile($path),
            [
                'id' => $id,
                'title' => $title,
                'collection' => $this->createCollection(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function getMediaManager(): MediaManager
    {
        return $this->getContainer()->get('sulu_media.media_manager');
    }

    private function getCollectionManager(): CollectionManager
    {
        return $this->getContainer()->get('sulu_media.collection_manager');
    }

    private function createMediaFile(string $name, string $fileName = 'photo.jpeg'): string
    {
        $filePath = \sys_get_temp_dir() . '/' . $name;
        \copy(__DIR__ . '/../../Fixtures/files/' . $fileName, $filePath);

        return $filePath;
    }
}
