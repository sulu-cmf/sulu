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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Traits;

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait CreateMediaTrait
{
    /**
     * @param array{
     *     title?: string,
     *     locale?: string,
     *     name?: string,
     *     key?: string,
     * } $data
     */
    private function createCollection(array $data = []): CollectionInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');

        $collection = new Collection();

        /** @var CollectionType|null $collectionType */
        $collectionType = $manager->getRepository(CollectionType::class)->find(1);
        if (!$collectionType) {
            $collectionType = new CollectionType();
            $collectionType->setId(1);
            $collectionType->setName($data['name'] ?? 'Default');
            $collectionType->setKey($data['key'] ?? 'default');
            $manager->persist($collectionType);
        }

        $collection->setType($collectionType);
        $meta = new CollectionMeta();
        $meta->setLocale($data['locale'] ?? 'en');
        $meta->setTitle($data['title'] ?? 'Example Collection');
        $meta->setCollection($collection);

        $collection->addMeta($meta);
        $collection->setDefaultMeta($meta);

        $manager->persist($collection);
        $manager->persist($meta);

        return $collection;
    }

    /**
     * @param array{
     *     name?: string,
     *     description?: string,
     * } $data
     */
    private function createMediaType(array $data): MediaType
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');

        $mediaType = new MediaType();
        $mediaType->setName($data['name'] ?? 'example');
        $mediaType->setDescription($data['description'] ?? 'Example Media Type');

        $manager->persist($mediaType);

        return $mediaType;
    }

    /**
     * @param array{
     *     title?: string,
     *     description?: string,
     *     locale?: string,
     * } $data
     */
    private function createMedia(
        CollectionInterface $collection,
        MediaType $mediaType,
        array $data = [],
    ): MediaInterface {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');

        $file = new \SplFileInfo(
            __DIR__ . \DIRECTORY_SEPARATOR . '..' . \DIRECTORY_SEPARATOR . 'assets' . \DIRECTORY_SEPARATOR . 'test-image.svg');
        $fileName = $file->getFilename();
        $uploadedFile = new UploadedFile($file->getPathname(), $fileName);

        $storageOptions = self::getContainer()->get('sulu_media.storage')->save(
            $uploadedFile->getPathname(),
            $fileName
        );

        $media = new Media();

        $file = new File();
        $file->setVersion(1)
            ->setMedia($media);

        $media->addFile($file)
            ->setType($mediaType)
            ->setCollection($collection);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion($file->getVersion())
            ->setSize($uploadedFile->getSize())
            ->setName($fileName)
            ->setStorageOptions($storageOptions)
            ->setMimeType($uploadedFile->getMimeType() ?: 'image/jpeg')
            ->setFile($file);

        $file->addFileVersion($fileVersion);

        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setTitle($data['title'] ?? 'Example Media')
            ->setDescription($data['description'] ?? 'Example Media description')
            ->setLocale($data['locale'] ?? 'en')
            ->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta)
            ->setDefaultMeta($fileVersionMeta);

        $manager->persist($fileVersionMeta);
        $manager->persist($fileVersion);
        $manager->persist($media);

        return $media;
    }

    abstract protected static function getContainer(): ContainerInterface;
}
