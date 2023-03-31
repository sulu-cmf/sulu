<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Mock;

use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;

class S3AdapterMock extends AwsS3Adapter implements AdapterInterface
{
    use StreamedTrait;
    use StreamedCopyTrait;

    private array $objectMap = [];

    public function addFile(string $filePath, string $content): void
    {
        $this->objectMap[$filePath] = $content;
    }

    public function addDirectory(string $directoryPath): void
    {
        $this->objectMap[$directoryPath] = null;
    }

    /**
     * Check whether a file is present.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        return \array_key_exists($path, $this->objectMap);
    }

    public function write($path, $contents, Config $config)
    {
        $this->objectMap[$path] = $contents;

        $type = 'file';
        $result = \compact('contents', 'type', 'path');

        if ($visibility = $config->get('visibility')) {
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    public function update($path, $contents, Config $config)
    {
        return false;
    }

    public function read($path)
    {
        if (\array_key_exists($path, $this->objectMap)) {
            return ['contents' => $this->objectMap[$path]];
        }

        return false;
    }

    public function rename($path, $newpath)
    {
        $this->objectMap[$newpath] = $this->objectMap[$path];
        unset($this->objectMap[$path]);

        return false;
    }

    public function delete($path)
    {
        unset($this->objectMap[$path]);

        return false;
    }

    public function listContents($directory = '', $recursive = false)
    {
        return [];
    }

    public function getMetadata($path)
    {
        return false;
    }

    public function getSize($path)
    {
        return false;
    }

    public function getMimetype($path)
    {
        return false;
    }

    public function getTimestamp($path)
    {
        return false;
    }

    public function getVisibility($path)
    {
        return false;
    }

    public function setVisibility($path, $visibility)
    {
        return \compact('visibility');
    }

    public function createDir($dirname, Config $config)
    {
        $this->objectMap[$dirname] = null;

        return ['path' => $dirname, 'type' => 'dir'];
    }

    public function deleteDir($dirname)
    {
        return false;
    }
}
