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

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a property with a value corresponding to the position of the node
 * relative to its siblings.
 */
class OrderSubscriber implements EventSubscriberInterface
{
    public const FIELD = 'order';

    public function __construct(
        private DocumentInspector $documentInspector,
        private PropertyEncoder $propertyEncoder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::REORDER => 'handleReorder',
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(OrderBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('suluOrder', [
            'encoding' => 'system',
            'property' => self::FIELD,
        ]);
    }

    /**
     * Adjusts the order of the document and its siblings.
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (false == $document instanceof OrderBehavior) {
            return;
        }

        if ($document->getSuluOrder()) {
            return;
        }

        $node = $event->getNode();
        $parent = $node->getParent();
        $nodeCount = \count($parent->getNodes());
        $order = ($nodeCount + 1) * 10;

        $document->setSuluOrder($order);
    }

    /**
     * Adjusts the order of the document and its siblings.
     */
    public function handleReorder(ReorderEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof OrderBehavior) {
            return;
        }

        $parentDocument = $this->documentInspector->getParent($document);

        if (null === $parentDocument) {
            return;
        }

        $count = 1;
        foreach ($this->documentInspector->getChildren($parentDocument) as $childDocument) {
            if (!$childDocument instanceof OrderBehavior) {
                continue;
            }

            $order = $count * 10;
            $childDocument->setSuluOrder($order);

            // TODO move to NodeHelper once integrated in sulu/sulu?
            $childNode = $this->documentInspector->getNode($childDocument);
            $childNode->setProperty($this->propertyEncoder->systemName(static::FIELD), $order);

            ++$count;
        }
    }
}
