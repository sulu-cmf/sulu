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

namespace Sulu\Bundle\SnippetBundle\EventListener;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<int, array{
     *     key: string,
     *     cache-invalidation: string
     * }> $areas
     */
    public function __construct(
        private DefaultSnippetManagerInterface $defaultSnippetManager,
        private ?CacheManager $cacheManager,
        private array $areas
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            WebspaceDefaultSnippetModifiedEvent::class => 'invalidateSnippetAreaOnAreaModified',
            WebspaceDefaultSnippetRemovedEvent::class => 'invalidateSnippetAreaOnAreaRemoved',
            SnippetModifiedEvent::class => 'invalidateSnippetAreaOnModified',
            SnippetRemovedEvent::class => 'invalidateSnippetAreaOnRemoved',
        ];
    }

    public function invalidateSnippetAreaOnModified(SnippetModifiedEvent $event): void
    {
        $this->invalidateSnippetArea($event->getResourceId());
    }

    public function invalidateSnippetAreaOnRemoved(SnippetRemovedEvent $event): void
    {
        $this->invalidateSnippetArea($event->getResourceId());
    }

    public function invalidateSnippetAreaOnAreaRemoved(WebspaceDefaultSnippetRemovedEvent $event): void
    {
        $this->invalidateSnippetArea($event->getResourceId(), $event->getSnippetAreaKey());
    }

    public function invalidateSnippetAreaOnAreaModified(WebspaceDefaultSnippetModifiedEvent $event): void
    {
        $this->invalidateSnippetArea($event->getResourceId(), $event->getSnippetAreaKey());
    }

    private function invalidateSnippetArea(string $snippetUuid, ?string $areaKey = null): void
    {
        if (!$this->cacheManager) {
            return;
        }

        if (null === $areaKey) {
            $areaKey = $this->defaultSnippetManager->loadType($snippetUuid);
        }

        foreach ($this->areas as $area) {
            if ($area['key'] !== $areaKey || 'false' === $area['cache-invalidation']) {
                continue;
            }

            $this->cacheManager->invalidateReference('snippet_area', $areaKey);
            break;
        }
    }
}
