<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Document\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\RouteIsNotUniqueException;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Route\Document\Behavior\RoutableBehavior;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to create/update/remove routes.
 */
class RoutableSubscriber implements EventSubscriberInterface
{
    public const ROUTE_PROPERTY = 'routePath';
    public const ROUTE_SUFFIX_PROPERTY = 'routePath-suffix';

    public const TAG_NAME = 'sulu_route.route_path';

    public function __construct(
        private ChainRouteGeneratorInterface $chainRouteGenerator,
        private RouteManagerInterface $routeManager,
        private RouteRepositoryInterface $routeRepository,
        private EntityManagerInterface $entityManager,
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
        private PropertyEncoder $propertyEncoder,
        private StructureMetadataFactoryInterface $metadataFactory,
        private ConflictResolverInterface $conflictResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::HYDRATE => ['handleHydrate'],
            Events::PERSIST => [
                // low priority because all other subscriber should be finished
                ['handlePersist', -2000],
            ],
            Events::REMOVE => [
                // high priority to ensure nodes are not deleted until we iterate over children
                ['handleRemove', 1024],
            ],
            Events::REMOVE_LOCALE => ['handleRemoveLocale', 1024],
            Events::PUBLISH => ['handlePublish', -2000],
            Events::COPY => ['handleCopy', -2000],
        ];
    }

    /**
     * Load route.
     */
    public function handleHydrate(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $locale = $document->getLocale();
        if (LocalizationState::SHADOW === $this->documentInspector->getLocalizationState($document)) {
            $locale = $document->getOriginalLocale();
        }

        $propertyName = $this->getRoutePathPropertyName($document, $locale);
        $routePath = $event->getNode()->getPropertyValueWithDefault($propertyName, null);
        $document->setRoutePath($routePath);

        $route = $this->routeRepository->findByEntity($document->getClass(), $document->getUuid(), $locale);
        if ($route) {
            $document->setRoute($route);
        }
    }

    /**
     * Generate route and save route-path.
     */
    public function handlePersist(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $document->setUuid($event->getNode()->getIdentifier());

        $propertyName = $this->getRoutePathPropertyName($document, $event->getLocale());
        $routePath = $event->getNode()->getPropertyValueWithDefault($propertyName, null);

        $route = $this->conflictResolver->resolve($this->chainRouteGenerator->generate($document, $routePath));
        $routePath = $route->getPath();
        $document->setRoutePath($route->getPath());

        $event->getNode()->setProperty($propertyName, $routePath);
    }

    /**
     * Removes route.
     */
    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $locales = $this->documentInspector->getLocales($document);
        foreach ($locales as $locale) {
            $localizedDocument = $this->documentManager->find($document->getUuid(), $locale);

            $route = $this->routeRepository->findByEntity(
                $localizedDocument->getClass(),
                $localizedDocument->getUuid(),
                $locale
            );
            if (!$route) {
                continue;
            }

            $this->entityManager->remove($route);
        }

        $this->entityManager->flush();
    }

    /**
     * Removes route for one locale.
     */
    public function handleRemoveLocale(RemoveLocaleEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $locale = $event->getLocale();
        $localizedDocument = $this->documentManager->find($document->getUuid(), $locale);

        $route = $this->routeRepository->findByEntity(
            $localizedDocument->getClass(),
            $localizedDocument->getUuid(),
            $locale
        );
        if (!$route) {
            return;
        }

        $this->entityManager->remove($route);
        $this->entityManager->flush();
    }

    /**
     * Handle publish event and generate route.
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    public function handlePublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        try {
            $route = $this->createOrUpdateRoute($document, $event->getLocale());
        } catch (RouteIsNotUniqueException $exception) {
            throw new ResourceLocatorAlreadyExistsException($exception->getRoute()->getPath(), $document->getPath(), $exception);
        }

        $document->setRoutePath($route->getPath());
        $this->entityManager->persist($route);

        $event->getNode()->setProperty(
            $this->getRoutePathPropertyName($document, $event->getLocale()),
            $route->getPath()
        );

        $this->setCorrectRouteSuffix($this->documentInspector->getNode($document), $route->getPath(), $event->getLocale());
        $this->setCorrectRouteSuffix($event->getNode(), $route->getPath(), $event->getLocale());
        $this->entityManager->flush();
    }

    /**
     * Update routes for copied document.
     */
    public function handleCopy(CopyEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $locales = $this->documentInspector->getLocales($document);
        foreach ($locales as $locale) {
            $localizedDocument = $this->documentManager->find($event->getCopiedPath(), $locale);

            if (!$localizedDocument instanceof RoutableBehavior) {
                return;
            }

            $route = $this->conflictResolver->resolve(
                $this->chainRouteGenerator->generate($localizedDocument, $localizedDocument->getRoutePath()),
            );
            $localizedDocument->setRoutePath($route->getPath());

            $node = $this->documentInspector->getNode($localizedDocument);
            $node->setProperty(
                $this->getRoutePathPropertyName($localizedDocument, $locale),
                $route->getPath()
            );
        }
    }

    /**
     * Create or update for given document.
     */
    private function createOrUpdateRoute(RoutableBehavior $document, string $locale): RouteInterface
    {
        $route = $document->getRoute();

        if (!$route) {
            $route = $this->routeRepository->findByEntity($document->getClass(), $document->getUuid(), $locale);
        }

        if ($route) {
            $document->setRoute($route);

            return $this->routeManager->update($document, $document->getRoutePath(), false);
        }

        return $this->routeManager->create($document, $document->getRoutePath(), false);
    }

    /**
     * Returns encoded "routePath" property-name.
     */
    private function getRoutePathPropertyName(RoutableBehavior $document, string $locale): string
    {
        $metadata = $this->documentInspector->getStructureMetadata($document);

        if ($metadata && $metadata->hasTag(self::TAG_NAME)) {
            return $this->getPropertyName(
                $locale,
                $metadata->getPropertyByTagName(self::TAG_NAME)->getName()
            );
        }

        return $this->getPropertyName($locale, self::ROUTE_PROPERTY);
    }

    /**
     * Returns encoded property-name.
     */
    private function getPropertyName(string $locale, string $field): string
    {
        return $this->propertyEncoder->localizedSystemName($field, $locale);
    }

    private function setCorrectRouteSuffix(NodeInterface $node, string $routePath, string $locale): void
    {
        $suffixName = $this->getPropertyName($locale, self::ROUTE_SUFFIX_PROPERTY);

        if (!$node->hasProperty($suffixName)) {
            return;
        }

        /** @var string $suffixValue */
        $suffixValue = $node->getPropertyValue($suffixName);

        $position = \strpos($routePath, $suffixValue);
        if (false !== $position) {
            $result = \substr($routePath, $position);
        } else {
            $result = $suffixValue;
        }

        $node->setProperty($suffixName, $result);
    }
}
