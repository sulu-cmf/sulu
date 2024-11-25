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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;

class ResourceLoaderProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetResourceLoader(): void
    {
        if (!\class_exists(MediaResourceLoader::class)) {
            $this->markTestSkipped('This test is skipped because the class "MediaResourceLoader" does not exist.');
        }

        $mediaResourceLoader = $this->prophesize(ResourceLoaderInterface::class);
        $categoryResourceLoader = $this->prophesize(ResourceLoaderInterface::class);
        $resourceLoaderProvider = new ResourceLoaderProvider(
            new \ArrayIterator([
                'media' => $mediaResourceLoader->reveal(),
                'category' => $categoryResourceLoader->reveal(),
            ])
        );

        self::assertSame($mediaResourceLoader->reveal(), $resourceLoaderProvider->getResourceLoader('media'));
        self::assertSame($categoryResourceLoader->reveal(), $resourceLoaderProvider->getResourceLoader('category'));
        self::assertNull($resourceLoaderProvider->getResourceLoader('invalid'));
    }
}
