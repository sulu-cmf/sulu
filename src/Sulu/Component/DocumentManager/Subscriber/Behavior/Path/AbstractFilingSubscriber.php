<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically set the parent at a pre-determined location.
 */
abstract class AbstractFilingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SessionInterface $defaultSession,
        private SessionInterface $liveSession,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => ['handlePersist', 490],
        ];
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $path = $this->generatePath($event);

        $currentDefaultNode = $this->defaultSession->getRootNode();
        $currentLiveNode = $this->liveSession->getRootNode();

        $pathSegments = \explode('/', \ltrim($path, '/'));
        foreach ($pathSegments as $pathSegment) {
            $uuid = UUIDHelper::generateUUID();
            $currentDefaultNode = $this->createNode($currentDefaultNode, $pathSegment, $uuid);
            $currentLiveNode = $this->createNode($currentLiveNode, $pathSegment, $uuid);
        }

        $event->setParentNode($currentDefaultNode);
    }

    /**
     * Generates the path for the given event.
     *
     * @return string
     */
    abstract protected function generatePath(PersistEvent $event);

    /**
     * Return true if this subscriber should be applied to the document.
     *
     * @param object $document
     */
    abstract protected function supports($document);

    /**
     * Return the name of the parent document.
     *
     * @param object $document
     *
     * @return string
     */
    abstract protected function getParentName($document);

    /**
     * Adds a node with the given path segment as a node name to the given node.
     *
     * @param string $pathSegment
     * @param string $uuid
     */
    private function createNode(NodeInterface $node, $pathSegment, $uuid)
    {
        if ($node->hasNode($pathSegment)) {
            return $node->getNode($pathSegment);
        }

        $node = $node->addNode($pathSegment);
        $node->addMixin('mix:referenceable');
        $node->setProperty('jcr:uuid', $uuid);

        return $node;
    }
}
