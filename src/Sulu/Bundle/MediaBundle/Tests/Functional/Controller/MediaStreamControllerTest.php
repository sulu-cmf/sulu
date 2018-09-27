<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaStreamControllerTest extends SuluTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    public function testDownloadAction()
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $media->getUrl());
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testNotExistVersionDownloadAction()
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', str_replace('v=1', 'v=99', $media->getUrl()));
        $response = $client->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testOldExistVersionDownloadAction()
    {
        $filePath = $this->createMediaFile('test.jpg');
        $oldMedia = $this->createMedia($filePath, 'file-without-extension');
        $newMedia = $this->createMediaVersion($oldMedia->getId(), $filePath, 'new-file-without-extension');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $oldMedia->getUrl());
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertEquals(
            sprintf(
                '<%s>; rel="canonical"',
                $newMedia->getUrl()
            ),
            $response->headers->get('Link')
        );
        $this->assertEquals(
            'noindex, follow',
            $response->headers->get('X-Robots-Tag')
        );
    }

    public function testDownloadWithoutExtensionAction()
    {
        $filePath = $this->createMediaFile('file-without-extension');
        $media = $this->createMedia($filePath, 'File without Extension');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $media->getUrl());
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testDownloadWithDotInName()
    {
        $filePath = $this->createMediaFile('fitness-seasons.agency--C-&-C--Rodach,-Johannes');
        $media = $this->createMedia($filePath, 'fitness-seasons.agency--C-&-C--Rodach,-Johannes');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $media->getUrl());
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->assertEquals(
            'attachment; filename=fitness-seasons.jpeg; filename*=utf-8\'\'fitness-seasons.agency--C-%26-C--Rodach%2C-Johannes',
            str_replace('"', '', $response->headers->get('Content-Disposition'))
        );
    }

    public function testGetImageActionForNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/uploads/media/sulu-400x400/01/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDownloadActionForNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/media/999/download/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    private function createUploadedFile($path)
    {
        return new UploadedFile($path, basename($path), mime_content_type($path), filesize($path));
    }

    private function createCollection($title = 'Test')
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

    private function createMedia($path, $title)
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

    private function createMediaVersion($id, $path, $title)
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

    private function getMediaManager()
    {
        return $this->getContainer()->get('sulu_media.media_manager');
    }

    private function getCollectionManager()
    {
        return $this->getContainer()->get('sulu_media.collection_manager');
    }

    private function createMediaFile($name)
    {
        $filePath = sys_get_temp_dir() . '/' . $name;
        copy(__DIR__ . '/../../app/Resources/images/photo.jpeg', $filePath);

        return $filePath;
    }
}
