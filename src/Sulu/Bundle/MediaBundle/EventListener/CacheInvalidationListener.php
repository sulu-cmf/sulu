<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

/**
 * Invalidate references when media is persisted.
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

        if ($object instanceof MediaInterface) {
            $cacheManager->invalidateReference('media', (string) $object->getId());
        } elseif ($object instanceof File) {
            $this->invalidateEntity($object->getMedia());
        } elseif ($object instanceof FileVersion) {
            $this->invalidateEntity($object->getFile());
            $this->invalidateTags($cacheManager, $object->getTags());
            $this->invalidateCategories($cacheManager, $object->getCategories());
        } elseif ($object instanceof FileVersionMeta) {
            $this->invalidateEntity($object->getFileVersion());
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
