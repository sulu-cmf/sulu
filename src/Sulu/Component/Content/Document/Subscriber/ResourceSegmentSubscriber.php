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
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
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
    public function __construct(
        private PropertyEncoder $encoder,
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
        private ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        private SessionInterface $defaultSession,
        private SessionInterface $liveSession,
    ) {
    }

    public static function getSubscribedEvents(): array
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
            Events::PUBLISH => ['handlePersistRoute', -128],
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
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $node = $event->getNode();
        $property = $this->getResourceSegmentProperty($document);

        if (!$property) {
            // do not set a resource segment if the document has no structure
            return;
        }

        $locale = $this->documentInspector->getOriginalLocale($document);
        $segment = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $property->getName(),
                $locale
            ),
            null
        );

        $document->setResourceSegment($segment);
    }

    /**
     * Sets the ResourceSegment on the Structure.
     */
    public function handlePersistDocument(PersistEvent $event)
    {
        /** @var ResourceSegmentBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $property = $this->getResourceSegmentProperty($document);
        // check if a property for the resource segment is available, this prevents the code from failing in case there
        // is no such property for some reason (e.g. the document doesn't have a structure)
        if ($property) {
            $this->persistDocument($document, $property);
        }
    }

    /**
     * Creates or updates the route for the document.
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

        $this->persistRoute($document);
    }

    /**
     * Moves the routes for all localizations of the document in the event.
     */
    public function updateMovedDocument(MoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ResourceSegmentBehavior) {
            return;
        }

        $webspaceKey = $this->documentInspector->getWebspace($event->getDocument());
        if (!$webspaceKey) {
            return;
        }

        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        if (ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF !== $resourceLocatorStrategy->getInputType()) {
            return;
        }

        $this->updateRoute($document, true);
    }

    /**
     * Copy the routes for all localization of the document in the event.
     */
    public function updateCopiedDocument(CopyEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ResourceSegmentBehavior) {
            return;
        }

        $this->updateRoute(
            $this->documentManager->find(
                $event->getCopiedPath(),
                $this->documentInspector->getLocale($document)
            ),
            false
        );
    }

    /**
     * Returns the property of the document's structure containing the ResourceSegment.
     *
     * @param object $document
     *
     * @return PropertyMetadata
     */
    private function getResourceSegmentProperty($document)
    {
        $structure = $this->documentInspector->getStructureMetadata($document);

        if (!$structure) {
            return;
        }

        $property = $structure->getPropertyByTagName('sulu.rlp');

        if (!$property) {
            throw new \RuntimeException(
                \sprintf(
                    'Structure "%s" does not have a "sulu.rlp" tag which is required for documents implementing the ' .
                    'ResourceSegmentBehavior. In "%s"',
                    $structure->getName(),
                    $structure->getResource()
                )
            );
        }

        return $property;
    }

    /**
     * Sets the ResourceSegment to the given property of the given document.
     */
    private function persistDocument(ResourceSegmentBehavior $document, PropertyMetadata $property)
    {
        $document->getStructure()->getProperty(
            $property->getName()
        )->setValue($document->getResourceSegment());
    }

    /**
     * Creates or updates the route of the document using the RlpStrategy.
     */
    private function persistRoute(ResourceSegmentBehavior $document)
    {
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(
            $this->documentInspector->getWebspace($document)
        );

        $resourceLocatorStrategy->save($document, null);
    }

    /**
     * Updates the route for the given document after a move or copy.
     *
     * @param object $document
     * @param bool $generateRoutes If set to true a route in the routing tree will also be created
     */
    private function updateRoute($document, $generateRoutes)
    {
        $locales = $this->documentInspector->getLocales($document);
        $webspaceKey = $this->documentInspector->getWebspace($document);
        $uuid = $this->documentInspector->getUuid($document);
        $path = $this->documentInspector->getPath($document);
        $parentUuid = $this->documentInspector->getUuid($this->documentInspector->getParent($document));

        $defaultNode = $this->defaultSession->getNode($path);
        $liveNode = $this->liveSession->getNode($path);

        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);

        foreach ($locales as $locale) {
            $localizedDocument = $this->documentManager->find($uuid, $locale);

            if (RedirectType::NONE !== $localizedDocument->getRedirectType()) {
                continue;
            }

            $resourceSegmentPropertyName = $this->encoder->localizedSystemName(
                $this->getResourceSegmentProperty($localizedDocument)->getName(),
                $locale
            );

            $this->updateResourceSegmentProperty(
                $defaultNode,
                $resourceSegmentPropertyName,
                $parentUuid,
                $webspaceKey,
                $locale
            );

            if ($liveNode->hasProperty($resourceSegmentPropertyName)) {
                $this->updateResourceSegmentProperty(
                    $liveNode,
                    $resourceSegmentPropertyName,
                    $parentUuid,
                    $webspaceKey,
                    $locale
                );

                // if the method is called with the generateRoutes flag it will create a new route
                // this happens on a move, but not on copy, because copy results in a draft page without url
                if ($generateRoutes) {
                    $localizedDocument->setResourceSegment($liveNode->getPropertyValue($resourceSegmentPropertyName));
                    $resourceLocatorStrategy->save($localizedDocument, null);
                    $localizedDocument->setResourceSegment($defaultNode->getPropertyValue($resourceSegmentPropertyName));
                }
            }
        }
    }

    /**
     * Updates the property for the resource segment on the given node.
     *
     * @param string $resourceSegmentPropertyName
     * @param string $parentUuid
     * @param string $webspaceKey
     * @param string $locale
     */
    private function updateResourceSegmentProperty(
        NodeInterface $node,
        $resourceSegmentPropertyName,
        $parentUuid,
        $webspaceKey,
        $locale
    ) {
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        $childPart = $resourceLocatorStrategy->getChildPart($node->getPropertyValue($resourceSegmentPropertyName));

        $node->setProperty(
            $resourceSegmentPropertyName,
            $resourceLocatorStrategy->generate($childPart, $parentUuid, $webspaceKey, $locale)
        );
    }
}
