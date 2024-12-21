<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber handles the live session.
 */
class PublishSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SessionInterface $liveSession,
        private NodeHelperInterface $nodeHelper,
        private PropertyEncoder $propertyEncoder,
        private MetadataFactoryInterface $metadataFactory,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['createNodeInPublicWorkspace', -490],
            Events::REMOVE => 'removeNodeFromPublicWorkspace',
            Events::MOVE => 'moveNodeInPublicWorkspace',
            Events::COPY => 'copyNodeInPublicWorkspace',
            Events::REORDER => 'reorderNodeInPublicWorkspace',
            Events::PUBLISH => ['setNodeFromPublicWorkspaceForPublishing', 512],
            Events::UNPUBLISH => [
                ['setNodeFromPublicWorkspaceForUnpublishing', 512],
                ['removePropertiesFromPublicWorkspace', 0],
            ],
            Events::REMOVE_DRAFT => 'copyPropertiesFromPublicWorkspace',
            Events::REMOVE_LOCALE => 'removeLocalePropertiesFromPublicWorkspace',
            Events::FLUSH => 'flushPublicWorkspace',
        ];
    }

    /**
     * Creates the node with the same UUID in the public workspace if it does not exist yet. In case it does it will
     * be renamed if necessary.
     */
    public function createNodeInPublicWorkspace(PersistEvent $event)
    {
        $node = $event->getNode();

        if ($node->isNew()) {
            $this->createNodesWithUuid($node);

            return;
        }

        try {
            $liveNode = $this->getLiveNode($event->getDocument());
        } catch (PathNotFoundException $e) {
            $this->createNodesWithUuid($node);

            return;
        }

        $nodeName = $node->getName();

        if ($liveNode->getName() !== $nodeName) {
            $liveNode->rename($nodeName);
        }
    }

    /**
     * Since deleting is not draftable the node will be deleted in the live session as soon as it is deleted in the
     * default session.
     */
    public function removeNodeFromPublicWorkspace(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $metadata = $this->metadataFactory->getMetadataForClass(\get_class($document));
        if (!$metadata->getSyncRemoveLive()) {
            return;
        }

        $this->getLiveNode($document)->remove();
    }

    /**
     * Since moving is not draftable the node will also be moved in the live session immediately.
     */
    public function moveNodeInPublicWorkspace(MoveEvent $event)
    {
        $liveNode = $this->getLiveNode($event->getDocument());
        $this->nodeHelper->move($liveNode, $event->getDestId(), $event->getDestName());
    }

    /**
     * If a node is copied a node with the same UUID will be created in the live session.
     */
    public function copyNodeInPublicWorkspace(CopyEvent $event)
    {
        $this->copyNodeWithChildrenInPublicWorkspace($event->getCopiedNode());
    }

    /**
     * Reordering is also not draftable, and therefore also immediately applied to the live session.
     */
    public function reorderNodeInPublicWorkspace(ReorderEvent $event)
    {
        $node = $this->getLiveNode($event->getDocument());

        $this->nodeHelper->reorder($node, $event->getDestId());

        // FIXME duplicating logic of OrderSubscriber, maybe move to NodeHelper?
        $count = 1;
        foreach ($node->getParent()->getNodes() as $childNode) {
            $childNode->setProperty($this->propertyEncoder->systemName('order'), $count * 10);
            ++$count;
        }
    }

    /**
     * Sets the correct node from the live session for the PublishEvent.
     */
    public function setNodeFromPublicWorkspaceForPublishing(PublishEvent $event)
    {
        // if the node is already set by another subscriber or to the event directly, we don't need to do anything.
        // One possible reason is the phpcr-cleanup command, which sets the node directly to the event.
        if ($event->hasNode()) {
            return;
        }

        $this->setNodeFromPublicWorkspace($event);
    }

    /**
     * Sets the correct node from the live session for the UnpublishEvent.
     */
    public function setNodeFromPublicWorkspaceForUnpublishing(UnpublishEvent $event)
    {
        $this->setNodeFromPublicWorkspace($event);
    }

    /**
     * Removes all the properties for the given locale from the node, so that the content is not accessible anymore from
     * the live workspace.
     */
    public function removePropertiesFromPublicWorkspace(UnpublishEvent $event)
    {
        $node = $event->getNode();
        $locale = $event->getLocale();

        $this->removeLocalizedNodeProperties($node, $locale);
    }

    public function copyPropertiesFromPublicWorkspace(RemoveDraftEvent $event)
    {
        $node = $event->getNode();
        $locale = $event->getLocale();

        $this->removeLocalizedNodeProperties($node, $locale);

        $liveNode = $this->getLiveNode($event->getDocument());

        // Copy all localized system and content properties from the live node
        foreach ($liveNode->getProperties($this->propertyEncoder->localizedSystemName('', $locale) . '*') as $property) {
            /* @var PropertyInterface $property */
            $node->setProperty($property->getName(), $property->getValue());
        }

        foreach ($liveNode->getProperties($this->propertyEncoder->localizedContentName('', $locale) . '*') as $property) {
            /** @var PropertyInterface $property */
            if ($node->hasProperty($property->getName())) {
                // skip the properties that have already been written by the previous loop
                // the properties haven't changed in the mean time, and writing them again would be unnecessary
                continue;
            }

            $node->setProperty($property->getName(), $property->getValue());
        }
    }

    public function removeLocalePropertiesFromPublicWorkspace(RemoveLocaleEvent $event)
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        $node = $event->getNode();
        $this->removeLocalizedNodeProperties($node, $locale);

        $liveNode = $this->getLiveNode($document);
        $this->removeLocalizedNodeProperties($liveNode, $locale);
    }

    /**
     * Flushes the live session.
     */
    public function flushPublicWorkspace()
    {
        $this->liveSession->save();
    }

    private function copyNodeWithChildrenInPublicWorkspace(NodeInterface $node)
    {
        $this->createNodesWithUuid($node);

        foreach ($node->getNodes() as $childNode) {
            $this->copyNodeWithChildrenInPublicWorkspace($childNode);
        }
    }

    /**
     * Creates every node on the path to the given node. Also uses the same UUIDs for these nodes.
     */
    private function createNodesWithUuid(NodeInterface $node)
    {
        $path = $node->getPath();

        if ($this->liveSession->itemExists($path)) {
            return;
        }

        $currentDefaultNode = $node->getSession()->getRootNode();
        $currentLiveNode = $this->liveSession->getRootNode();

        $pathSegments = \explode('/', \ltrim($path, '/'));
        foreach ($pathSegments as $pathSegment) {
            $currentDefaultNode = $currentDefaultNode->getNode($pathSegment);

            if ($currentLiveNode->hasNode($pathSegment)) {
                $currentLiveNode = $currentLiveNode->getNode($pathSegment);

                continue;
            }

            $currentLiveNode = $currentLiveNode->addNode($pathSegment);
            $currentLiveNode->setMixins(['mix:referenceable']);
            $currentLiveNode->setProperty('jcr:uuid', $currentDefaultNode->getIdentifier());
        }
    }

    /**
     * Returns the live node for given document.
     *
     * @return NodeInterface
     */
    private function getLiveNode(PathBehavior $document)
    {
        return $this->liveSession->getNode($document->getPath());
    }

    /**
     * Sets the node from the live workspace on the given event.
     *
     * @param PublishEvent|UnpublishEvent $event
     */
    private function setNodeFromPublicWorkspace($event)
    {
        $event->setNode($this->getLiveNode($event->getDocument()));
    }

    /**
     * Removes all localized properties in the given locale from the given node.
     *
     * @param string $locale
     */
    private function removeLocalizedNodeProperties(NodeInterface $node, $locale)
    {
        // remove all localized system properties from the node
        foreach ($node->getProperties($this->propertyEncoder->localizedSystemName('', $locale) . '*') as $property) {
            $property->remove();
        }

        // remove all localized content properties from the node
        foreach ($node->getProperties($this->propertyEncoder->localizedContentName('', $locale) . '*') as $property) {
            $property->remove();
        }
    }
}
