<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * TODO: This could be made into a pure metadata subscriber if we make
 *       the resource locator a system property.
 */
class ResourceSegmentSubscriber implements EventSubscriberInterface
{
    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        RlpStrategyInterface $rlpStrategy
    ) {
        $this->encoder = $encoder;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->rlpStrategy = $rlpStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // persist should happen before content is mapped
            Events::PERSIST => [
                ['handlePersistDocument', 10],
            ],
            // hydrate should happen afterwards
            Events::HYDRATE => ['handleHydrate', -200],
            Events::MOVE => ['updateMovedDocument', -128],
            Events::COPY => ['updateCopiedDocument', -128],
            Events::PUBLISH => 'handlePersistRoute',
        ];
    }

    /**
     * Checks if the given Document supports the operations done in this Subscriber.
     *
     * @param object $document
     *
     * @return bool
     */
    public function supports($document)
    {
        return $document instanceof ResourceSegmentBehavior && $document instanceof StructureBehavior;
    }

    /**
     * Sets the ResourceSegment of the document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $node = $event->getNode();
        $property = $this->getResourceSegmentProperty($document);
        $locale = $this->documentInspector->getOriginalLocale($document);
        $segment = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $property->getName(),
                $locale
            ),
            ''
        );

        $document->setResourceSegment($segment);
    }

    /**
     * Sets the ResourceSegment on the Structure.
     *
     * @param PersistEvent $event
     */
    public function handlePersistDocument(PersistEvent $event)
    {
        /** @var ResourceSegmentBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $property = $this->getResourceSegmentProperty($document);
        $this->persistDocument($document, $property);
    }

    /**
     * Creates or updates the route for the document.
     *
     * @param PublishEvent $event
     */
    public function handlePersistRoute(PublishEvent $event)
    {
        /** @var ResourceSegmentBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        if ($document instanceof HomeDocument) {
            return;
        }

        if ($document instanceof RedirectTypeBehavior && $document->getRedirectType() !== RedirectType::NONE) {
            return;
        }

        $this->persistRoute($document);
    }

    /**
     * Moves the routes for all localizations of the document in the event.
     *
     * @param MoveEvent $event
     */
    public function updateMovedDocument(MoveEvent $event)
    {
        $this->updateRoute($event->getDocument(), true);
    }

    /**
     * Copy the routes for all localization of the document in the event.
     *
     * @param CopyEvent $event
     */
    public function updateCopiedDocument(CopyEvent $event)
    {
        $this->updateRoute(
            $this->documentManager->find(
                $event->getCopiedPath(),
                $this->documentInspector->getLocale($event->getDocument())
            ),
            false
        );
    }

    /**
     * Returns the property of the document's structure containing the ResourceSegment.
     *
     * @param $document
     *
     * @return PropertyMetadata
     */
    private function getResourceSegmentProperty($document)
    {
        $structure = $this->documentInspector->getStructureMetadata($document);
        $property = $structure->getPropertyByTagName('sulu.rlp');

        if (!$property) {
            throw new \RuntimeException(
                sprintf(
                    'Structure "%s" does not have a "sulu.rlp" tag which is required for documents implementing the ' .
                    'ResourceSegmentBehavior. In "%s"',
                    $structure->name,
                    $structure->resource
                )
            );
        }

        return $property;
    }

    /**
     * Sets the ResourceSegment to the given property of the given document.
     *
     * @param ResourceSegmentBehavior $document
     * @param PropertyMetadata $property
     */
    private function persistDocument(ResourceSegmentBehavior $document, PropertyMetadata $property)
    {
        $document->getStructure()->getProperty(
            $property->getName()
        )->setValue($document->getResourceSegment());
    }

    /**
     * Creates or updates the route of the document using the RlpStrategy.
     *
     * @param ResourceSegmentBehavior $document
     */
    private function persistRoute(ResourceSegmentBehavior $document)
    {
        $this->rlpStrategy->save($document, null);
    }

    /**
     * Updates the route for the given document after a move or copy.
     *
     * @param object $document
     * @param bool $generateRoutes If set to true a route in the routing tree will also be created
     */
    private function updateRoute($document, $generateRoutes)
    {
        if (!$document instanceof ResourceSegmentBehavior) {
            return;
        }

        $locales = $this->documentInspector->getLocales($document);
        $webspaceKey = $this->documentInspector->getWebspace($document);
        $uuid = $this->documentInspector->getUuid($document);
        $parentUuid = $this->documentInspector->getUuid($this->documentInspector->getParent($document));

        foreach ($locales as $locale) {
            $localizedDocument = $this->documentManager->find($uuid, $locale);

            if ($localizedDocument->getRedirectType() !== RedirectType::NONE) {
                continue;
            }

            try {
                $parentPart = $this->rlpStrategy->loadByContentUuid($parentUuid, $webspaceKey, $locale);
            } catch (ResourceLocatorNotFoundException $e) {
                $parentPart = null;
            }

            $childPart = $this->rlpStrategy->getChildPart($localizedDocument->getResourceSegment());

            $localizedDocument->setResourceSegment(
                $this->rlpStrategy->generate($childPart, $parentPart, $webspaceKey, $locale)
            );

            $this->documentManager->persist($localizedDocument, $locale);

            if ($generateRoutes) {
                $this->rlpStrategy->save($localizedDocument, null);
            }
        }
    }
}
