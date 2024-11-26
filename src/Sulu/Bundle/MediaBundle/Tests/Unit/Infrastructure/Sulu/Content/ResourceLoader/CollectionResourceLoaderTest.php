<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Collection as ApiCollection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\CollectionResourceLoader;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class CollectionResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<CollectionManagerInterface>
     */
    private ObjectProphecy $collectionManager;

    private CollectionResourceLoader $loader;

    public function setUp(): void
    {
        $this->collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $this->loader = new CollectionResourceLoader($this->collectionManager->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('collection', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $collection1 = $this->createCollection(1);
        $collection3 = $this->createCollection(3);

        $this->collectionManager->getById(1, 'en')->willReturn($collection1)
            ->shouldBeCalled();

        $this->collectionManager->getById(3, 'en')->willReturn($collection3)
            ->shouldBeCalled();

        $result = $this->loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $collection1,
            3 => $collection3,
        ], $result);
    }

    private static function createCollection(int $id): ApiCollection
    {
        $collection = new Collection();
        static::setPrivateProperty($collection, 'id', $id);

        return new ApiCollection($collection, 'en');
    }
}
