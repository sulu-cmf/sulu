<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\Util\PathHelper;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Behavior for route (sulu:path) documents.
 */
class RouteSubscriber implements EventSubscriberInterface
{
    public const DOCUMENT_HISTORY_FIELD = 'history';

    public const NODE_HISTORY_FIELD = 'sulu:history';

    public function __construct(
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
        private SessionManagerInterface $sessionManager,
        private NodeManager $nodeManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => [
                ['handleSetNodeOnPersist', 490],
                // must be executed before the TargetSubscriber
                ['handlePersist', 5],
            ],
            Events::HYDRATE => 'handleHydrate',
            Events::REMOVE => ['handleRemove', 550],
            Events::PUBLISH => 'handlePublish',
        ];
    }

    /**
     * Writes the history status of the node to the document.
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $document->setHistory($event->getNode()->getPropertyValue(self::NODE_HISTORY_FIELD));
    }

    /**
     * Receives node for route and overwrite when the node is empty.
     */
    public function handleSetNodeOnPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        $options = $event->getOptions();
        if (!$document instanceof RouteBehavior || !\array_key_exists('path', $options)) {
            return;
        }

        $parentPath = PathHelper::getParentPath($options['path']);
        $parentNode = $this->nodeManager->createPath($parentPath);

        $nodeName = PathHelper::getNodeName($options['path']);
        if (!$parentNode->hasNode($nodeName)) {
            return;
        }

        $node = $parentNode->getNode($nodeName);
        if ($node->hasProperty(self::NODE_HISTORY_FIELD)) {
            return;
        }

        $event->setNode($node);
        $event->setParentNode($parentNode);
    }

    /**
     * Updates the route for the given document and creates history routes if necessary.
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty(self::NODE_HISTORY_FIELD, $document->isHistory());

        $targetDocument = $document->getTargetDocument();

        if ($targetDocument instanceof HomeDocument
            || !$targetDocument instanceof WebspaceBehavior
            || !$targetDocument instanceof ResourceSegmentBehavior
        ) {
            return;
        }

        // copy new route to old position
        $webspaceKey = $targetDocument->getWebspaceName();
        $locale = $this->documentInspector->getLocale($document);

        $routePath = $this->sessionManager->getRoutePath($webspaceKey, $locale, null)
            . $targetDocument->getResourceSegment();

        // create a route node if it is not a new document and the path changed
        $documentPath = $this->documentInspector->getPath($document);
        if ($documentPath && $documentPath != $routePath) {
            /** @var RouteDocument $newRouteDocument */
            $newRouteDocument = $this->documentManager->create('route');
            $newRouteDocument->setTargetDocument($targetDocument);
            $this->documentManager->persist(
                $newRouteDocument,
                $locale,
                [
                    'path' => $routePath,
                    'auto_create' => true,
                ]
            );
            $this->documentManager->publish($newRouteDocument, $locale);

            // change routes in old position to history
            $this->changeOldPathToHistoryRoutes($document, $newRouteDocument);
        }
    }

    /**
     * Removes the routes for the given document and removes history routes if necessary.
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $this->recursivelyRemoveRoutes($document);
    }

    /**
     * Handles the history field for the route on publish.
     */
    public function handlePublish(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $event->getNode()->setProperty(self::NODE_HISTORY_FIELD, $document->isHistory());
    }

    /**
     * Remove given Route and his history.
     */
    private function recursivelyRemoveRoutes(RouteBehavior $document)
    {
        $referrers = $this->documentInspector->getReferrers($document);

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof RouteBehavior) {
                continue;
            }

            $this->recursivelyRemoveRoutes($referrer);
            $this->documentManager->remove($referrer);
        }
    }

    /**
     * Changes the old route to a history route and redirect to the new route.
     */
    private function changeOldPathToHistoryRoutes(RouteBehavior $oldDocument, RouteBehavior $newDocument)
    {
        $oldDocument->setTargetDocument($newDocument);
        $oldDocument->setHistory(true);
        $oldRouteNode = $this->documentInspector->getNode($oldDocument);
        $oldRouteNode->setProperty(self::NODE_HISTORY_FIELD, true);

        foreach ($this->documentInspector->getReferrers($oldDocument) as $referrer) {
            if ($referrer instanceof RouteBehavior) {
                $referrer->setTargetDocument($newDocument);
                $referrer->setHistory(true);
                $this->documentManager->persist(
                    $referrer,
                    null,
                    [
                        'path' => $this->documentInspector->getPath($referrer),
                    ]
                );
                $this->documentManager->publish($referrer, null);
            }
        }
    }
}
