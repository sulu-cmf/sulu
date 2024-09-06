<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document\Subscriber;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * When a content has changed or been removed we need to invalidate all custom url routes that point to the content
 * When a custom route has been removed we need to invalidate all the caches too.
 */
class InvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private ?CacheManager $cacheManager,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PUBLISH => ['invalidateDocumentBeforePublishing', 1024],
            Events::REMOVE => ['invalidateDocumentBeforeRemoving', 1024],
        ];
    }

    public function invalidateDocumentBeforePublishing(PublishEvent $event): void
    {
        $this->invalidateDocument($event->getDocument());
    }

    public function invalidateDocumentBeforeRemoving(RemoveEvent $event): void
    {
        $this->invalidateDocument($event->getDocument());
    }

    private function invalidateDocument(BasePageDocument|RouteDocument|CustomUrl|CustomUrlRoute $document): void
    {
        if (!$this->cacheManager) {
            return;
        }

        if ($document instanceof BasePageDocument) {
            foreach ($this->customUrlRepository->findByTarget($document) as $customUrlDocument) {
                $this->invalidateCustomUrlDocument($customUrlDocument);
            }
        } elseif ($document instanceof RouteDocument) {
            $this->invalidateDocument($document->getTargetDocument());
        } elseif ($document instanceof CustomUrl) {
            $this->invalidateCustomUrlDocument($document);
        } else {
            $this->invalidateCustomUrl($document);
        }
    }

    private function invalidateCustomUrlDocument(CustomUrl $customUrl): void
    {
        foreach ($customUrl->getRoutes() as $route) {
            $this->cacheManager->invalidatePath($this->getUrlWithScheme($route->getPath()));
        }
    }

    private function invalidateCustomUrl(CustomUrlRoute $customUrlRoute): void
    {
        $this->cacheManager->invalidatePath($this->getUrlWithScheme($customUrlRoute->getPath()));
    }

    private function getUrlWithScheme(string $url): string
    {
        $scheme = 'http';
        if ($request = $this->requestStack->getCurrentRequest()) {
            $scheme = $request->getScheme();
        }

        return \sprintf('%s://%s', $scheme, $url);
    }
}
