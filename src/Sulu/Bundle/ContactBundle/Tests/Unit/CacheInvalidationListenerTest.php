<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\EventListener\CacheInvalidationListener;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class CacheInvalidationListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CacheManager>
     */
    private $cacheManager;

    /**
     * @var CacheInvalidationListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->cacheManager = $this->prophesize(CacheManager::class);

        $this->listener = new CacheInvalidationListener($this->cacheManager->reveal());
    }

    public static function provideData()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
            [\stdClass::class, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testPostPersist($class, $alias): void
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn(new ArrayCollection([]));
            $entity->getCategories()->willReturn(new ArrayCollection([]));

            $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->postPersist($eventArgs->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testPostUpdate($class, $alias): void
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn(new ArrayCollection([]));
            $entity->getCategories()->willReturn(new ArrayCollection([]));

            $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->postUpdate($eventArgs->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testPreRemove($class, $alias): void
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn(new ArrayCollection([]));
            $entity->getCategories()->willReturn(new ArrayCollection([]));

            $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->preRemove($eventArgs->reveal());
    }

    public static function provideDataWithTagsAndCategories()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataWithTagsAndCategories')]
    public function testPersistUpdateWithTagsAndCategories($class, $alias): void
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn(new ArrayCollection([$tags[0]->reveal(), $tags[1]->reveal()]));
        $this->cacheManager->invalidateReference('tag', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn(new ArrayCollection([$categories[0]->reveal(), $categories[1]->reveal()]));
        $this->cacheManager->invalidateReference('category', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->postPersist($eventArgs->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataWithTagsAndCategories')]
    public function testPostUpdateWithTagsAndCategories($class, $alias): void
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn(new ArrayCollection([$tags[0]->reveal(), $tags[1]->reveal()]));
        $this->cacheManager->invalidateReference('tag', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn(new ArrayCollection([$categories[0]->reveal(), $categories[1]->reveal()]));
        $this->cacheManager->invalidateReference('category', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->postUpdate($eventArgs->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataWithTagsAndCategories')]
    public function testPreRemoveUpdateWithTagsAndCategories($class, $alias): void
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn(new ArrayCollection([$tags[0]->reveal(), $tags[1]->reveal()]));
        $this->cacheManager->invalidateReference('tag', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn(new ArrayCollection([$categories[0]->reveal(), $categories[1]->reveal()]));
        $this->cacheManager->invalidateReference('category', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->cacheManager->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->preRemove($eventArgs->reveal());
    }
}
