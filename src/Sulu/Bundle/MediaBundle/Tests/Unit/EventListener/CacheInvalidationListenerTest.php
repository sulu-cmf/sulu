<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\EventListener\CacheInvalidationListener;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class CacheInvalidationListenerTest extends TestCase
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var CacheInvalidationListener
     */
    private $listener;

    protected function setUp()
    {
        $this->cacheManager = $this->prophesize(CacheManager::class);

        $this->listener = new CacheInvalidationListener($this->cacheManager->reveal());
    }

    public function provideFunctionName()
    {
        return [
            ['postPersist'],
            ['postUpdate'],
            ['preRemove'],
        ];
    }

    /**
     * @dataProvider provideFunctionName
     */
    public function testPostUpdate($functionName)
    {
        $entity = $this->prophesize(MediaInterface::class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $entity->getId()->willReturn(1);
        $this->cacheManager->invalidateReference('media', 1)->shouldBeCalled();

        $this->listener->{$functionName}($eventArgs->reveal());
    }

    /**
     * @dataProvider provideFunctionName
     */
    public function testPostUpdateFile($functionName)
    {
        $media = $this->prophesize(MediaInterface::class);
        $entity = $this->prophesize(File::class);
        $entity->getMedia()->willReturn($media->reveal());

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $media->getId()->willReturn(1);
        $this->cacheManager->invalidateReference('media', 1)->shouldBeCalled();

        $this->listener->{$functionName}($eventArgs->reveal());
    }

    /**
     * @dataProvider provideFunctionName
     */
    public function testPostUpdateFileVersion($functionName)
    {
        $media = $this->prophesize(MediaInterface::class);
        $file = $this->prophesize(File::class);
        $entity = $this->prophesize(FileVersion::class);
        $entity->getFile()->willReturn($file->reveal());
        $file->getMedia()->willReturn($media->reveal());

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn($tags);
        $this->cacheManager->invalidateReference('tag', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn($categories);
        $this->cacheManager->invalidateReference('category', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $media->getId()->willReturn(1);
        $this->cacheManager->invalidateReference('media', 1)->shouldBeCalled();

        $this->listener->{$functionName}($eventArgs->reveal());
    }

    /**
     * @dataProvider provideFunctionName
     */
    public function testPostUpdateFileVersionMeta($functionName)
    {
        $media = $this->prophesize(MediaInterface::class);
        $file = $this->prophesize(File::class);
        $fileVersion = $this->prophesize(FileVersion::class);
        $fileVersion->getTags()->willReturn([]);
        $fileVersion->getCategories()->willReturn([]);
        $entity = $this->prophesize(FileVersionMeta::class);
        $entity->getFileVersion()->willReturn($fileVersion->reveal());
        $fileVersion->getFile()->willReturn($file->reveal());
        $file->getMedia()->willReturn($media->reveal());

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $media->getId()->willReturn(1);
        $this->cacheManager->invalidateReference('media', 1)->shouldBeCalled();

        $this->listener->{$functionName}($eventArgs->reveal());
    }

    /**
     * @dataProvider provideFunctionName
     */
    public function testPostUpdateOther($functionName)
    {
        $entity = $this->prophesize(\stdClass::class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();

        $this->listener->{$functionName}($eventArgs->reveal());
    }
}
