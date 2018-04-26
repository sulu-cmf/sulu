<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Doctrine\ORM\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\EventListener\CacheInvalidationListener;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Contact\Model\ContactInterface;

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

    public function provideData()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
            [\stdClass::class, null],
        ];
    }

    /**
     * @dataProvider provideData
     */
    public function testPostPersist($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn([]);
            $entity->getCategories()->willReturn([]);

            $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->postPersist($eventArgs->reveal());
    }

    /**
     * @dataProvider provideData
     */
    public function testPostUpdate($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn([]);
            $entity->getCategories()->willReturn([]);

            $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->postUpdate($eventArgs->reveal());
    }

    /**
     * @dataProvider provideData
     */
    public function testPreRemove($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn([]);
            $entity->getCategories()->willReturn([]);

            $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->preRemove($eventArgs->reveal());
    }

    public function provideDataWithTagsAndCategories()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
        ];
    }

    /**
     * @dataProvider provideDataWithTagsAndCategories
     */
    public function testPersistUpdateWithTagsAndCategories($class, $alias)
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

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

        $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->postPersist($eventArgs->reveal());
    }

    /**
     * @dataProvider provideDataWithTagsAndCategories
     */
    public function testPostUpdateWithTagsAndCategories($class, $alias)
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

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

        $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->postUpdate($eventArgs->reveal());
    }

    /**
     * @dataProvider provideDataWithTagsAndCategories
     */
    public function testPreRemoveUpdateWithTagsAndCategories($class, $alias)
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

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

        $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->preRemove($eventArgs->reveal());
    }
}
