<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventSubscriber;

use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\WebsiteBundle\Domain\Event\CacheClearedEvent;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Sulu\Bundle\WebsiteBundle\Events;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class DomainEventEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var DomainEventDispatcherInterface
     */
    private $domainEventDispatcher;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(
        DomainEventDispatcherInterface $domainEventDispatcher,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->domainEventDispatcher = $domainEventDispatcher;
        $this->webspaceManager = $webspaceManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::CACHE_CLEAR => 'onCacheClear',
        ];
    }

    public function onCacheClear(CacheClearEvent $event): void
    {
        $tags = $event->getTags();
        if (null === $tags || 0 === \count($tags)) {
            /** @var Webspace $webspace */
            foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
                $this->domainEventDispatcher->dispatch(new CacheClearedEvent($webspace->getKey(), null));
            }

            return;
        }

        foreach ($tags as $tag) {
            $webspaceKey = $this->getWebspaceKeyFromTag($tag);
            if ($webspaceKey) {
                $this->domainEventDispatcher->dispatch(new CacheClearedEvent($webspaceKey, $tags));
            }
        }
    }

    private function getWebspaceKeyFromTag(string $tag): ?string
    {
        $parts = \explode('-', $tag, 2);

        if (!isset($parts[1]) || WebspaceReferenceStore::WEBSPACE_REFERENCE_ALIAS !== $parts[0]) {
            return null;
        }

        return $parts[1];
    }
}
