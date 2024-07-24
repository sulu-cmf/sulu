<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\SessionInterface;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to create/update/remove routes.
 */
class RoutableSubscriber implements EventSubscriberInterface
{
    public const ROUTE_FIELD = 'routePath';

    public const ROUTE_FIELD_NAME = self::ROUTE_FIELD . 'Name';

    public const ROUTES_PROPERTY = 'suluRoutes';

    public const TAG_NAME = 'sulu_article.article_route';

    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var RouteManagerInterface
     */
    private $routeManager;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    public function __construct(
        ChainRouteGeneratorInterface $chainRouteGenerator,
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        EntityManagerInterface $entityManager,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        PropertyEncoder $propertyEncoder,
        StructureMetadataFactoryInterface $metadataFactory,
        ConflictResolverInterface $conflictResolver
    ) {
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->entityManager = $entityManager;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->propertyEncoder = $propertyEncoder;
        $this->metadataFactory = $metadataFactory;
        $this->conflictResolver = $conflictResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => [
                // low priority because all other subscriber should be finished
                ['handlePersist', -2048],
            ],
            Events::REMOVE => [
                // high priority to ensure nodes are not deleted until we iterate over children
                ['handleRemove', 2048],
            ],
            Events::PUBLISH => ['handlePublish', -2048],
            Events::REORDER => ['handleReorder', -1024],
            Events::COPY => ['handleCopy', -2048],
        ];
    }

    /**
     * Generate route and save route-path.
     */
    public function handlePersist(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof RoutablePageBehavior || !$document instanceof ChildrenBehavior) {
            return;
        }

        $this->updateChildRoutes($document);
    }

    /**
     * Regenerate routes for siblings on reorder.
     */
    public function handleReorder(ReorderEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutablePageBehavior || !$document instanceof ParentBehavior) {
            return;
        }

        $parentDocument = $document->getParent();
        if (!$parentDocument instanceof ChildrenBehavior) {
            return;
        }

        $this->updateChildRoutes($parentDocument);
    }

    /**
     * Handle publish event and generate route and the child-routes.
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    public function handlePublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $propertyName = $this->getPropertyName($event->getLocale(), self::ROUTES_PROPERTY);

        // check if nodes previous generated routes exists and remove them if not
        $oldRoutes = $event->getNode()->getPropertyValueWithDefault($propertyName, []);
        $this->removeOldChildRoutes($event->getNode()->getSession(), $oldRoutes, $event->getLocale());

        $routes = [];
        if ($document instanceof ChildrenBehavior) {
            // generate new routes of children
            $routes = $this->generateChildRoutes($document, $event->getLocale());
        }

        // save the newly generated routes of children
        $event->getNode()->setProperty($propertyName, $routes);
        $this->entityManager->flush();
    }

    /**
     * Removes route.
     */
    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior || !$document instanceof ChildrenBehavior) {
            return;
        }

        $locales = $this->documentInspector->getLocales($document);
        foreach ($locales as $locale) {
            $this->removeChildRoutes($document, $locale);
        }

        $this->entityManager->flush();
    }

    /**
     * Update routes for copied article.
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

            if ($localizedDocument instanceof ChildrenBehavior) {
                $this->generateChildRoutes($localizedDocument, $locale);
            }
        }
    }

    /**
     * Create or update for given document.
     */
    private function createOrUpdatePageRoute(RoutablePageBehavior $document, string $locale): RouteInterface
    {
        $route = $this->reallocateExistingRoute($document, $locale);
        if ($route) {
            return $route;
        }

        $route = $document->getRoute();
        if (!$route) {
            $route = $this->routeRepository->findByEntity($document->getClass(), $document->getUuid(), $locale);
        }

        if ($route && $route->getEntityId() !== $document->getId()) {
            // Mismatch of entity-id's happens because doctrine don't check entities which has been changed in the
            // current session.

            $document->removeRoute();
            $route = null;
        }

        if ($route) {
            $document->setRoute($route);

            return $this->routeManager->update($document, null, false);
        }

        return $this->routeManager->create($document);
    }

    /**
     * Reallocates existing route to given document.
     */
    private function reallocateExistingRoute(RoutablePageBehavior $document, string $locale): ?RouteInterface
    {
        $newRoute = $this->routeRepository->findByPath($document->getRoutePath(), $locale);
        if (!$newRoute) {
            return null;
        }

        $oldRoute = $this->routeRepository->findByEntity(\get_class($document), $document->getUuid(), $locale);
        $history = $this->routeRepository->findHistoryByEntity(\get_class($document), $document->getUuid(), $locale);

        /** @var RouteInterface $historyRoute */
        foreach (\array_filter(\array_merge($history, [$oldRoute])) as $historyRoute) {
            if ($historyRoute->getId() === $newRoute->getId() || $document->getId() !== $historyRoute->getEntityId()) {
                // Mismatch of entity-id's happens because doctrine don't check entities which has been changed in the
                // current session. If the old-route was already reused by a page before it will be returned in the
                // query of line 329.

                continue;
            }

            $historyRoute->setTarget($newRoute);
            $historyRoute->setHistory(true);
            $newRoute->addHistory($historyRoute);
        }

        $newRoute->setEntityClass(\get_class($document));
        $newRoute->setEntityId($document->getId());
        $newRoute->setTarget(null);
        $newRoute->setHistory(false);

        return $newRoute;
    }

    private function updateRoute(RoutablePageBehavior $document): void
    {
        $locale = $this->documentInspector->getLocale($document);
        $propertyName = $this->getRoutePathPropertyName((string) $document->getStructureType(), $locale);

        $route = $this->chainRouteGenerator->generate($document);
        $document->setRoutePath($route->getPath());

        $node = $this->documentInspector->getNode($document);
        $node->setProperty($propertyName, $route->getPath());
        $node->setProperty($this->propertyEncoder->localizedContentName(self::ROUTE_FIELD_NAME, (string) $locale), $propertyName);
    }

    private function updateChildRoutes(ChildrenBehavior $document): void
    {
        foreach ($document->getChildren() as $childDocument) {
            if (!$childDocument instanceof RoutablePageBehavior) {
                continue;
            }

            $this->updateRoute($childDocument);
        }
    }

    /**
     * Generates child routes.
     *
     * @return string[]
     */
    private function generateChildRoutes(ChildrenBehavior $document, string $locale): array
    {
        $routes = [];
        foreach ($document->getChildren() as $child) {
            if (!$child instanceof RoutablePageBehavior) {
                continue;
            }

            $childRoute = $this->createOrUpdatePageRoute($child, $locale);
            $this->entityManager->persist($childRoute);

            $child->setRoutePath($childRoute->getPath());
            $childNode = $this->documentInspector->getNode($child);

            $propertyName = $this->getRoutePathPropertyName((string) $child->getStructureType(), $locale);
            $childNode->setProperty($propertyName, $childRoute->getPath());

            $routes[] = $childRoute->getPath();
        }

        return $routes;
    }

    /**
     * Removes old-routes where the node does not exists anymore.
     */
    private function removeOldChildRoutes(SessionInterface $session, array $oldRoutes, string $locale): void
    {
        foreach ($oldRoutes as $oldRoute) {
            $oldRouteEntity = $this->routeRepository->findByPath($oldRoute, $locale);
            if ($oldRouteEntity && !$this->nodeExists($session, $oldRouteEntity->getEntityId())) {
                $this->entityManager->remove($oldRouteEntity);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Iterate over children and remove routes.
     */
    private function removeChildRoutes(ChildrenBehavior $document, string $locale): void
    {
        foreach ($document->getChildren() as $child) {
            if ($child instanceof RoutablePageBehavior) {
                $this->removeChildRoute($child, $locale);
            }

            if ($child instanceof ChildrenBehavior) {
                $this->removeChildRoutes($child, $locale);
            }
        }
    }

    /**
     * Removes route if exists.
     */
    private function removeChildRoute(RoutablePageBehavior $document, string $locale): void
    {
        $route = $this->routeRepository->findByPath($document->getRoutePath(), $locale);
        if ($route) {
            $this->entityManager->remove($route);
        }
    }

    /**
     * Returns encoded "routePath" property-name.
     */
    private function getRoutePathPropertyName(string $structureType, string $locale): string
    {
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        if ($metadata->hasTag(self::TAG_NAME)) {
            return $this->getPropertyName($locale, $metadata->getPropertyByTagName(self::TAG_NAME)->getName());
        }

        return $this->getPropertyName($locale, self::ROUTE_FIELD);
    }

    /**
     * Returns encoded property-name.
     */
    private function getPropertyName(string $locale, string $field): string
    {
        return $this->propertyEncoder->localizedSystemName($field, $locale);
    }

    /**
     * Returns true if given uuid exists.
     */
    private function nodeExists(SessionInterface $session, string $uuid): bool
    {
        try {
            $session->getNodeByIdentifier($uuid);

            return true;
        } catch (ItemNotFoundException $exception) {
            return false;
        }
    }
}
