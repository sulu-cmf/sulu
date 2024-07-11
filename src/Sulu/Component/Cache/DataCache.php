<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DataCache manages serialized data cached in a file.
 *
 * When the file exists the cache uses the data in the
 * file and does not rely on other files.
 */
class DataCache implements CacheInterface
{
    public function __construct(private string $file)
    {
    }

    public function read()
    {
        if (!\is_file($this->file)) {
            return;
        }

        return \unserialize(\file_get_contents($this->file));
    }

    public function write($data)
    {
        $mode = 0666;
        $umask = \umask();
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->file, \serialize($data));

        try {
            $filesystem->chmod($this->file, $mode, $umask);
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }
    }

    public function invalidate()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->file);
    }

    public function isFresh()
    {
        return \is_file($this->file);
    }
}
