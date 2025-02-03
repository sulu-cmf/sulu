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

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove routes and references associated with content.
 */
class StructureRemoveSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
        private SessionInterface $defaultSession,
        private SessionInterface $liveSession,
        private MetadataFactoryInterface $metadataFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REMOVE => ['handleRemove', 550],
        ];
    }

    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $this->removeDocument($document);
    }

    public function removeDocument($document)
    {
        if ($document instanceof ChildrenBehavior) {
            foreach ($document->getChildren() as $child) {
                $this->removeDocument($child);
            }
        }

        if ($document instanceof StructureBehavior) {
            $this->removeReferences($document);
            $this->removeRoute($document);
        }
    }

    /**
     * Removes related route of given document.
     */
    private function removeRoute(StructureBehavior $document)
    {
        foreach ($this->documentInspector->getReferrers($document) as $referrer) {
            if ($referrer instanceof RouteBehavior) {
                $this->documentManager->remove($referrer);
            }
        }
    }

    private function removeReferences($document)
    {
        $node = $this->documentInspector->getNode($document);

        $this->removeReferencesForNode($this->defaultSession->getNode($node->getPath()));
        $this->removeReferencesForNode($this->liveSession->getNode($node->getPath()));
    }

    private function removeReferencesForNode(NodeInterface $node)
    {
        $references = $node->getReferences();

        foreach ($references as $reference) {
            $referrer = $reference->getParent();
            $metadata = $this->metadataFactory->getMetadataForPhpcrNode($referrer);

            if (RouteDocument::class === $metadata->getClass()) {
                continue;
            }

            $this->dereferenceProperty($node, $reference);
        }
    }

    /**
     * Remove the given property, or the value which references the node (when
     * multi-valued).
     */
    private function dereferenceProperty(NodeInterface $node, PropertyInterface $property)
    {
        if (false === $property->isMultiple()) {
            $property->remove();

            return;
        }

        // dereference from multi-valued referring properties
        $values = $property->getValue();
        foreach ($values as $i => $referencedNode) {
            if ($referencedNode->getIdentifier() === $node->getIdentifier()) {
                unset($values[$i]);
            }
        }

        $property->getParent()->setProperty($property->getName(), $values);
    }
}
