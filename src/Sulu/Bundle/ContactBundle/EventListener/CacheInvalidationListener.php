<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

/**
 * Invalidate references when account/contact are persisted.
 */
class CacheInvalidationListener
{
    public function __construct(private ?CacheManagerInterface $cacheManager)
    {
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $eventArgs
     *
     * @return void
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $this->invalidateEntity($eventArgs->getObject());
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $eventArgs
     *
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->invalidateEntity($eventArgs->getObject());
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $eventArgs
     *
     * @return void
     */
    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $this->invalidateEntity($eventArgs->getObject());
    }

    /**
     * @param object $object
     *
     * @return void
     */
    private function invalidateEntity($object)
    {
        $cacheManager = $this->cacheManager;

        if (!$cacheManager) {
            return;
        }

        if ($object instanceof ContactInterface) {
            $cacheManager->invalidateReference('contact', (string) $object->getId());
            $this->invalidateTags($cacheManager, $object->getTags());
            $this->invalidateCategories($cacheManager, $object->getCategories());
        } elseif ($object instanceof AccountInterface) {
            $cacheManager->invalidateReference('account', (string) $object->getId());
            $this->invalidateTags($cacheManager, $object->getTags());
            $this->invalidateCategories($cacheManager, $object->getCategories());
        }
    }

    /**
     * @param Collection<int, TagInterface> $tags
     *
     * @return void
     */
    private function invalidateTags(CacheManagerInterface $cacheManager, $tags)
    {
        foreach ($tags as $tag) {
            $cacheManager->invalidateReference('tag', (string) $tag->getId());
        }
    }

    /**
     * @param Collection<int, CategoryInterface> $categories
     *
     * @return void
     */
    private function invalidateCategories(CacheManagerInterface $cacheManager, $categories)
    {
        foreach ($categories as $category) {
            $cacheManager->invalidateReference('category', (string) $category->getId());
        }
    }
}
