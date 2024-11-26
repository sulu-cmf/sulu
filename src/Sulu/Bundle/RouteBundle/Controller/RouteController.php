<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\RouteBundle\Domain\Event\RouteRemovedEvent;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Model\ResourceLocatorParts;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides api to handle routes.
 */
class RouteController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private RouteRepositoryInterface $routeRepository,
        private EntityManagerInterface $entityManager,
        private RouteGeneratorInterface $routeGenerator,
        private array $resourceKeyMappings,
        private ConflictResolverInterface $conflictResolver,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
        parent::__construct($viewHandler);

        if (null === $this->conflictResolver) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.3',
                'Instantiating RouteController without the $conflictResolver argument is deprecated.'
            );
        }

        if (null == $this->domainEventCollector) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.5',
                'Instantiating RouteController without the $domainEventCollector argument is deprecated.'
            );
        }
    }

    public function postAction(Request $request): Response
    {
        $action = $request->query->get('action');

        return match ($action) {
            'generate' => $this->generateUrlResponse($request),
            default => throw new RestException('Unrecognized action: ' . $action),
        };
    }

    /**
     * Returns list of routes for given entity.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        // required parameter
        $locale = $this->getRequestParameter($request, 'locale', true);
        $resourceKey = $this->getRequestParameter($request, 'resourceKey', true);
        $id = $this->getRequestParameter($request, 'id', true);

        // optional parameter
        $history = $this->getBooleanRequestParameter($request, 'history', false, false);
        $mapping = $this->resourceKeyMappings[$resourceKey] ?? [];
        $entityClass = $this->getRequestParameter($request, 'entityClass') ?? $mapping['entityClass'] ?? null;

        if (!$entityClass) {
            throw new NotFoundHttpException(\sprintf('No route mapping configured for resourceKey "%s"', $resourceKey));
        }

        $routes = $this->findRoutes($history, $entityClass, $id, $locale);

        return $this->handleView($this->view(new CollectionRepresentation($routes, 'routes')));
    }

    /**
     * Find routes with given parameters.
     *
     * @param bool $history
     * @param string $entityClass
     * @param string $entityId
     * @param string $locale
     *
     * @return RouteInterface|RouteInterface[]
     */
    private function findRoutes($history, $entityClass, $entityId, $locale)
    {
        if ($history) {
            return $this->routeRepository->findHistoryByEntity($entityClass, $entityId, $locale);
        }

        return [$this->routeRepository->findByEntity($entityClass, $entityId, $locale)];
    }

    /**
     * Delete given history-route.
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function cdeleteAction(Request $request)
    {
        $ids = \explode(',', $request->get('ids'));

        foreach ($ids as $id) {
            $route = $this->routeRepository->find($id);
            if (!$route) {
                throw new EntityNotFoundException(RouteInterface::class, $id);
            }

            $this->entityManager->remove($route);
            /** @var string $resourceKey */
            $resourceKey = \defined($route->getEntityClass() . '::RESOURCE_KEY') ? \constant($route->getEntityClass() . '::RESOURCE_KEY') : RouteInterface::RESOURCE_KEY;
            if ($this->domainEventCollector instanceof DomainEventCollectorInterface) {
                $this->domainEventCollector->collect(
                    new RouteRemovedEvent(
                        $route->getId(),
                        $route->getPath(),
                        $route->getLocale(),
                        $route->getEntityId(),
                        $route->getEntityClass(),
                        $resourceKey
                    )
                );
            }
        }

        $this->entityManager->flush();

        return $this->handleView($this->view());
    }

    private function generateUrlResponse(Request $request)
    {
        $resourceKey = $this->getRequestParameter($request, 'resourceKey');
        $locale = $this->getRequestParameter($request, 'locale');

        $mapping = $this->resourceKeyMappings[$resourceKey] ?? [];
        $entityClass = $this->getRequestParameter($request, 'entityClass') ?? $mapping['entityClass'] ?? null;
        $routeSchema = $this->getRequestParameter($request, 'routeSchema') ?? $mapping['options']['route_schema'] ?? null;

        /** @var array $parts */
        $parts = $this->getRequestParameter($request, 'parts', true);
        $route = '/' . \implode('-', $parts);

        if ($entityClass && $routeSchema) {
            $options = $mapping['options'] ?? [];
            $options['route_schema'] = $routeSchema;
            $options['locale'] = $locale;

            $route = $this->routeGenerator->generate(new ResourceLocatorParts($parts), $options);

            if ($this->conflictResolver) {
                // create temporary route that is not persisted to resolve possible conflicts with existing routes
                $tempRouteEntity = $this->routeRepository->createNew()
                    ->setPath($route)
                    ->setLocale($locale)
                    ->setEntityClass($entityClass)
                    ->setEntityId($this->getRequestParameter($request, 'id'));
                $tempRouteEntity = $this->conflictResolver->resolve($tempRouteEntity);

                $route = $tempRouteEntity->getPath();
            }
        }

        return $this->handleView($this->view(['resourcelocator' => $route]));
    }
}
