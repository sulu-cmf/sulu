<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Audit;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Behavior\Audit\LocalizedTimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manage the timestamp (created, changed) fields on documents before they are persisted.
 */
class TimestampSubscriber implements EventSubscriberInterface
{
    public const CREATED = 'created';

    public const CHANGED = 'changed';

    public function __construct(
        private PropertyEncoder $propertyEncoder,
        private DocumentInspector $documentInspector,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => 'setTimestampsOnNodeForPersist',
            Events::PUBLISH => 'setTimestampsOnNodeForPublish',
            Events::RESTORE => ['setChangedForRestore', -32],
            Events::HYDRATE => 'setTimestampsOnDocument',
        ];
    }

    /**
     * Sets the timestamps from the node to the document.
     */
    public function setTimestampsOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->supports($document)) {
            return;
        }

        $accessor = $event->getAccessor();
        $node = $event->getNode();
        $locale = $this->documentInspector->getOriginalLocale($document);

        $encoding = $this->getPropertyEncoding($document);

        $accessor->set(
            static::CHANGED,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, static::CHANGED, $locale),
                null
            )
        );
        $accessor->set(
            static::CREATED,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, static::CREATED, $locale),
                null
            )
        );
    }

    /**
     * Sets the timestamps on the nodes for the persist operation.
     */
    public function setTimestampsOnNodeForPersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->setTimestampsOnNode(
            $document,
            $event->getNode(),
            $event->getAccessor(),
            $this->documentInspector->getOriginalLocale($document),
            new \DateTime()
        );
    }

    public function setTimestampsOnNodeForPublish(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->setTimestampsOnNode(
            $document,
            $event->getNode(),
            $event->getAccessor(),
            $this->documentInspector->getOriginalLocale($document)
        );
    }

    /**
     * Set the timestamps on the node.
     *
     * @param string $locale
     * @param \DateTime|null $timestamp The timestamp to set, will use the documents timestamps if null is provided
     */
    public function setTimestampsOnNode(
        LocalizedTimestampBehavior $document,
        NodeInterface $node,
        DocumentAccessor $accessor,
        $locale,
        $timestamp = null
    ) {
        if (!$document instanceof TimestampBehavior && !$locale) {
            return;
        }

        $encoding = $this->getPropertyEncoding($document);

        $createdPropertyName = $this->propertyEncoder->encode($encoding, static::CREATED, $locale);
        if (!$node->hasProperty($createdPropertyName)) {
            $createdTimestamp = $document->getCreated() ?: $timestamp;
            $accessor->set(static::CREATED, $createdTimestamp);
            $node->setProperty($createdPropertyName, $createdTimestamp);
        }

        $changedTimestamp = $timestamp ?: $document->getChanged();
        $accessor->set(static::CHANGED, $changedTimestamp);
        $node->setProperty($this->propertyEncoder->encode($encoding, static::CHANGED, $locale), $changedTimestamp);
    }

    /**
     * Sets the changed timestamp when restoring a document.
     */
    public function setChangedForRestore(RestoreEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->supports($document)) {
            return;
        }

        $encoding = $this->getPropertyEncoding($document);

        $event->getNode()->setProperty(
            $this->propertyEncoder->encode(
                $encoding,
                static::CHANGED,
                $this->documentInspector->getOriginalLocale($document)
            ),
            new \DateTime()
        );
    }

    /**
     * Returns the encoding for the given document.
     *
     * @param object $document
     *
     * @return string
     */
    private function getPropertyEncoding($document)
    {
        $encoding = 'system_localized';
        if ($document instanceof TimestampBehavior) {
            $encoding = 'system';
        }

        return $encoding;
    }

    /**
     * Return true if document is supported by this subscriber.
     *
     * @param object $document
     *
     * @return bool
     */
    private function supports($document)
    {
        return $document instanceof LocalizedTimestampBehavior;
    }
}
