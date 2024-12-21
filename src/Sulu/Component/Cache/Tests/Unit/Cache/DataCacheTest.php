<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Cache\CacheInterface;
use Sulu\Component\Cache\DataCache;
use Symfony\Component\Filesystem\Filesystem;

class DataCacheTest extends TestCase
{
    public static function provideIsFreshData()
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'sulu-test');

        return [
            [$tmpFile, false, false],
            [$tmpFile, true, true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideIsFreshData')]
    public function testIsFresh($file, $createFile, $expected): void
    {
        $filesystem = new Filesystem();
        if ($createFile) {
            $filesystem->touch($file);
        } elseif ($filesystem->exists($file)) {
            $filesystem->remove($file);
        }

        $cache = new DataCache($file);

        $this->assertEquals($expected, $cache->isFresh());
    }

    public function testWrite(): DataCache
    {
        $file = \tempnam(\sys_get_temp_dir(), 'sulu-test');
        $cache = new DataCache($file);

        $cache->write(['test' => 'test']);

        $this->assertEquals(\serialize(['test' => 'test']), \file_get_contents($file));

        return $cache;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testWrite')]
    public function testRead(CacheInterface $cache): void
    {
        $this->assertEquals(['test' => 'test'], $cache->read());
    }
}
