<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document\Subscriber;

use PHPCR\Util\PathHelper;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Invalidate custom-url http-cache.
 */
class InvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomUrlManagerInterface
     */
    private $customUrlManager;

    /**
     * @var null|CacheManager
     */
    private $cacheManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        CustomUrlManagerInterface $customUrlManager,
        DocumentInspector $documentInspector,
        ?CacheManager $cacheManager,
        RequestStack $requestStack
    ) {
        $this->customUrlManager = $customUrlManager;
        $this->cacheManager = $cacheManager;
        $this->documentInspector = $documentInspector;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PUBLISH => ['invalidateDocumentBeforePublishing', 1024],
            Events::REMOVE => ['invalidateDocumentBeforeRemoving', 1024],
        ];
    }

    /**
     * Invalidate custom-urls before publishing.
     */
    public function invalidateDocumentBeforePublishing(PublishEvent $event)
    {
        $this->invalidateDocument($event->getDocument());
    }

    /**
     * Invalidate custom-urls before removing.
     */
    public function invalidateDocumentBeforeRemoving(RemoveEvent $event)
    {
        $this->invalidateDocument($event->getDocument());
    }

    private function invalidateDocument($document)
    {
        if ($document instanceof BasePageDocument) {
            /** @var CustomUrlDocument $customUrlDocument */
            foreach ($this->customUrlManager->findByPage($document) as $customUrlDocument) {
                $this->invalidateCustomUrlDocument($customUrlDocument);
            }
        }

        if ($document instanceof CustomUrlDocument) {
            $this->invalidateCustomUrlDocument($document);
        }

        if ($document instanceof RouteDocument) {
            $this->invalidateRouteDocument($document);
        }
    }

    private function invalidateCustomUrlDocument(CustomUrlDocument $document)
    {
        if (!$this->cacheManager) {
            return;
        }

        foreach ($document->getRoutes() as $route => $routeDocument) {
            $this->cacheManager->invalidatePath($this->getUrlWithScheme($route));
        }
    }

    private function invalidateRouteDocument(RouteDocument $routeDocument)
    {
        if (!$this->cacheManager) {
            return;
        }

        $url = PathHelper::relativizePath(
            $routeDocument->getPath(),
            $this->customUrlManager->getRoutesPath($this->documentInspector->getWebspace($routeDocument))
        );

        $this->cacheManager->invalidatePath($this->getUrlWithScheme($url));
    }

    private function getUrlWithScheme(string $url)
    {
        $scheme = 'http';
        if ($request = $this->requestStack->getCurrentRequest()) {
            $scheme = $request->getScheme();
        }

        return sprintf('%s://%s', $scheme, $url);
    }
}
