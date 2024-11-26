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

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\Visibility;
use PHPCR\Shell\Config\Exception\FileExistsException as PHPCRFileExistsException;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;

/** @phpstan-import-type StorageOptions from StorageInterface */
class FlysystemStorage implements StorageInterface
{
    public function __construct(
        private ?FilesystemOperator $filesystem,
        private ?FilesystemAdapter $adapter,
        private int $segments,
    ) {
    }

    public function save(string $tempPath, string $fileName, array $storageOptions = []): array
    {
        if (!\array_key_exists('segment', $storageOptions)) {
            // Generating a string based on the segment. It zero fills the random value it generates. So if the value
            // of segments is 100 and the random value is 5 it returns the string 005
            $storageOptions['segment'] = \sprintf(
                '%0' . \strlen((string) $this->segments) . 'd',
                \rand(1, $this->segments),
            );
        }

        $this->createDirectories($storageOptions);

        $parentPath = $this->getFilePath(\array_merge($storageOptions, ['fileName' => null]));
        $storageOptions['fileName'] = $this->getUniqueFileName($parentPath, $fileName);

        $filePath = $this->getFilePath($storageOptions);

        try {
            $this->filesystem->writeStream(
                $filePath,
                \fopen($tempPath, 'r'),
                ['visibility' => Visibility::PUBLIC]
            );
        } catch (PHPCRFileExistsException $exception) {
            throw new FilenameAlreadyExistsException($filePath);
        }

        return $storageOptions;
    }

    public function load(array $storageOptions)
    {
        $filePath = $this->getFilePath($storageOptions);

        try {
            return $this->filesystem->readStream($filePath);
        } catch (UnableToReadFile $exception) {
            throw new ImageProxyMediaNotFoundException(\sprintf('Original media at path "%s" not found', $filePath));
        }
    }

    public function remove(array $storageOptions): void
    {
        $filePath = $this->getFilePath($storageOptions);

        if (!$filePath) {
            return;
        }

        try {
            $this->filesystem->delete($filePath);
        } catch (UnableToDeleteFile $exception) {
        }
    }

    public function move(array $sourceStorageOptions, array $targetStorageOptions): array
    {
        $this->createDirectories($targetStorageOptions);

        $targetParentPath = $this->getFilePath(\array_merge($targetStorageOptions, ['fileName' => null]));
        $targetStorageOptions['fileName'] = $this->getUniqueFileName($targetParentPath, $targetStorageOptions['fileName']);

        $targetFilePath = $this->getFilePath($targetStorageOptions);
        if ($this->filesystem->has($targetFilePath)) {
            throw new FilenameAlreadyExistsException($targetFilePath);
        }

        $this->filesystem->move($this->getFilePath($sourceStorageOptions), $targetFilePath);

        return $targetStorageOptions;
    }

    protected function getUniqueFileName(string $parentPath, string $fileName, int $counter = 0): string
    {
        $newFileName = $fileName;
        if ($counter > 0) {
            $fileNameParts = \explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter;
            if (isset($fileNameParts[1])) {
                $newFileName .= '.' . $fileNameParts[1];
            }
        }

        $filePath = \rtrim($parentPath, '/') . '/' . \ltrim($newFileName, '/');

        if (!$this->filesystem->has($filePath)) {
            return $newFileName;
        }

        return $this->getUniqueFileName($parentPath, $fileName, $counter + 1);
    }

    /**
     * @param StorageOptions $storageOptions
     */
    protected function getStorageOption(array $storageOptions, string $key): ?string
    {
        return $storageOptions[$key] ?? null;
    }

    /**
     * @param StorageOptions $storageOptions
     */
    protected function getFilePath(array $storageOptions): string
    {
        $directory = $this->getStorageOption($storageOptions, 'directory');
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        return \implode('/', \array_filter([$directory, $segment, $fileName]));
    }

    /**
     * @param StorageOptions $storageOptions
     */
    private function createDirectories(array $storageOptions): void
    {
        $directory = $this->getStorageOption($storageOptions, 'directory');
        $directoryPath = \implode('/', \array_filter([$directory]));

        if ($directoryPath && !$this->filesystem->has($directoryPath)) {
            $this->filesystem->createDirectory($directoryPath);
        }

        $segment = $this->getStorageOption($storageOptions, 'segment');
        $segmentPath = \implode('/', \array_filter([$directory, $segment]));

        if ($segmentPath && !$this->filesystem->has($segmentPath)) {
            $this->filesystem->createDirectory($segmentPath);
        }
    }

    public function getPath(array $storageOptions): string
    {
        return $this->getFilePath($storageOptions);
    }

    public function getType(array $storageOptions): string
    {
        if ($this->adapter instanceof LocalFilesystemAdapter) {
            return StorageInterface::TYPE_LOCAL;
        }

        return StorageInterface::TYPE_REMOTE;
    }
}
