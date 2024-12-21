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

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class S3Storage extends FlysystemStorage
{
    /**
     * @var AwsS3Adapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $bucketName;

    /**
     * @var string|null
     */
    private $publicUrl;

    public function __construct(FilesystemInterface $filesystem, int $segments, ?string $publicUrl = null)
    {
        parent::__construct($filesystem, $segments);

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof AwsS3Adapter) {
            throw new \RuntimeException('This storage can only handle filesystems with "AwsS3Adapter".');
        }

        $this->adapter = $filesystem->getAdapter();

        $this->endpoint = (string) $this->adapter->getClient()->getEndpoint();
        $this->bucketName = $this->adapter->getBucket();

        $this->publicUrl = null !== $publicUrl ? $publicUrl : ($this->endpoint . '/' . $this->bucketName);
    }

    public function getPath(array $storageOptions): string
    {
        $filePath = $this->getFilePath($storageOptions);
        $path = $this->adapter->applyPathPrefix($filePath);

        return $this->publicUrl . '/' . \ltrim($path, '/');
    }

    public function getType(array $storageOptions): string
    {
        return StorageInterface::TYPE_REMOTE;
    }
}
