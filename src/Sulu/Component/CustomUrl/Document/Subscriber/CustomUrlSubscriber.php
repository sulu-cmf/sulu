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

use PHPCR\PathNotFoundException;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events for custom-urls.
 */
class CustomUrlSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private GeneratorInterface $generator,
        private DocumentManagerInterface $documentManager,
        private PathBuilder $pathBuilder,
        protected DocumentInspector $inspector,
        private WebspaceManagerInterface $webspaceManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::REMOVE => ['handleRemove', 550],
        ];
    }

    /**
     * Creates routes for persisted custom-url.
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        $webspaceKey = $this->inspector->getWebspace($document);
        $domain = $this->generator->generate($document->getBaseDomain(), $document->getDomainParts());
        $locale = $this->webspaceManager->findWebspaceByKey($webspaceKey)->getLocalization(
            $document->getTargetLocale()
        );
        $route = $this->createRoute(
            $domain,
            $document,
            $locale,
            $event->getLocale(),
            $this->getRoutesPath($webspaceKey)
        );

        $this->updateOldReferrers($document, $route, $event->getLocale());
    }

    private function updateOldReferrers($document, $newRoute, $locale)
    {
        $oldReferrers = $this->inspector->getReferrers($document);

        try {
            foreach ($oldReferrers as $oldReferrer) {
                if (
                    !$oldReferrer instanceof RouteDocument
                    || $oldReferrer->getPath() === $newRoute->getPath()
                ) {
                    continue;
                }

                $oldReferrer->setTargetDocument($newRoute);
                $oldReferrer->setHistory(true);
                $this->documentManager->persist(
                    $oldReferrer,
                    $locale,
                    [
                        'path' => $oldReferrer->getPath(),
                        'auto_create' => true,
                    ]
                );
                $this->documentManager->publish($oldReferrer, $locale);

                $this->updateOldReferrers($oldReferrer, $newRoute, $locale);
            }
        } catch (PathNotFoundException $e) {
            // Avoid error if node does not exist yet
        }
    }

    /**
     * Create route-document for given domain.
     *
     * @param string $domain
     * @param string $persistedLocale
     * @param string $routesPath
     *
     * @return RouteDocument
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    protected function createRoute(
        $domain,
        CustomUrlBehavior $document,
        Localization $locale,
        $persistedLocale,
        $routesPath
    ) {
        $path = \sprintf('%s/%s', $routesPath, $domain);
        $routeDocument = $this->findOrCreateRoute($path, $persistedLocale, $document, $domain);
        $routeDocument->setTargetDocument($document);
        $routeDocument->setLocale($locale->getLocale());
        $routeDocument->setHistory(false);

        $this->documentManager->persist(
            $routeDocument,
            $persistedLocale,
            [
                'path' => $path,
                'auto_create' => true,
            ]
        );
        $this->documentManager->publish($routeDocument, $persistedLocale);

        return $routeDocument;
    }

    /**
     * Find or create route-document for given path.
     *
     * @param string $path
     * @param string $locale
     * @param string $route
     *
     * @return RouteDocument
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    protected function findOrCreateRoute($path, $locale, CustomUrlBehavior $document, $route)
    {
        try {
            /** @var RouteDocument $routeDocument */
            $routeDocument = $this->documentManager->find($path, $locale);
        } catch (DocumentNotFoundException $ex) {
            return $this->documentManager->create('custom_url_route');
        }

        if (!$routeDocument instanceof RouteDocument
            || $routeDocument->getTargetDocument()->getUuid() !== $document->getUuid()
        ) {
            throw new ResourceLocatorAlreadyExistsException($route, $document->getTitle());
        }

        return $routeDocument;
    }

    /**
     * Removes the routes for the given document.
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        foreach ($this->inspector->getReferrers($document) as $referrer) {
            if ($referrer instanceof RouteBehavior) {
                $this->documentManager->remove($referrer);
            }
        }
    }

    /**
     * Return routes path for custom-url in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    protected function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_routes%']);
    }
}
