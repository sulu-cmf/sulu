<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

/**
 * Defines the operations of the StorageLayer.
 * The StorageLayer is a interface to centralized management of media store.
 *
 *  @phpstan-type StorageOptions array{
 * "directory" ?: string,
 * "segments" ?: int|string,
 * "fileName" ?: string,
 * }
 */
interface StorageInterface
{
    public const TYPE_REMOTE = 'remote';

    public const TYPE_LOCAL = 'local';

    /**
     * Save the document in the storage and return storage options of the stored document.
     *
     * @param StorageOptions $storageOptions
     *
     * @return StorageOptions
     */
    public function save(string $tempPath, string $fileName, array $storageOptions = []): array;

    /**
     * Returns the content for the given file as a resource.
     *
     * @param StorageOptions $storageOptions
     *
     * @return resource
     */
    public function load(array $storageOptions);

    /**
     * Returns the path for the given file.
     *
     * @param StorageOptions $storageOptions
     */
    public function getPath(array $storageOptions): string;

    /**
     * Returns the type for the given file.
     *
     * @param StorageOptions $storageOptions
     */
    public function getType(array $storageOptions): string;

    /**
     * Moves a file on the storage.
     *
     * @param StorageOptions $sourceStorageOptions
     * @param StorageOptions $targetStorageOptions
     *
     * @return StorageOptions
     */
    public function move(array $sourceStorageOptions, array $targetStorageOptions): array;

    /**
     * Removes the file from storage.
     *
     * @param StorageOptions $storageOptions
     */
    public function remove(array $storageOptions): void;
}
