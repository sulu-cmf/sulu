<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PreviewBundle\Preview\PreviewCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PreviewCacheTest extends TestCase
{
    public function testPreviewCache(): void
    {
        $previewCache = new PreviewCache(DoctrineProvider::wrap(new ArrayAdapter()));

        $this->assertFalse($previewCache->contains('id'));
        $previewCache->save('id', 'test');
        $this->assertTrue($previewCache->contains('id'));
        $this->assertSame('test', $previewCache->fetch('id'));
        $previewCache->delete('id');
        $this->assertFalse($previewCache->contains('id'));
    }

    public function testLegacyPreviewCache(): void
    {
        $previewCache = new PreviewCache(DoctrineProvider::wrap(new ArrayAdapter()));

        $this->assertFalse($previewCache->contains('id'));
        $previewCache->save('id', 'test');
        $this->assertTrue($previewCache->contains('id'));
        $this->assertSame('test', $previewCache->fetch('id'));
        $previewCache->delete('id');
        $this->assertFalse($previewCache->contains('id'));
    }
}
