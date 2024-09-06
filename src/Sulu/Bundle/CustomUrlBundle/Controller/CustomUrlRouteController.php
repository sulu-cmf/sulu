<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

/**
 * @RouteResource("route")
 */
class CustomUrlRouteController extends AbstractRestController implements SecuredControllerInterface
{
    private static string $relationName = 'custom_url_routes';

    /**
     * @var ObjectRepository<EntityRepository<CustomUrlRoute>>
     */
    private ObjectRepository $customUrlRouteRepository;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
        parent::__construct($viewHandler);
        $this->customUrlRouteRepository = $this->entityManager->getRepository(CustomUrlRoute::class);
    }

    public function cgetAction(string $webspace, string $id, Request $request): Response
    {
        // Get all routes for the current CustomUrl, but skip the newest (because that's the currently in use route)
        /** @var array<CustomUrlRoute> $historicRoutes */
        $historicRoutes = $this->customUrlRouteRepository->findBy(
            criteria: ['customUrl' => $id],
            orderBy: ['created' => 'DESC'],
            offset: 1
        );

        $result = \array_map(
            fn (CustomUrlRoute $historyRoute) => [
                'id' => $historyRoute->getId(),
                'resourcelocator' => $historyRoute->getPath(),
                'created' => $historyRoute->getCreated(),
            ],
            $historicRoutes
        );

        $list = new CollectionRepresentation($result, self::$relationName);

        return $this->handleView($this->view($list));
    }

    public function cdeleteAction(string $webspace, string $id, Request $request): Response
    {
        $ids = [];
        foreach (\explode(',', $request->attributes->getString('ids', '')) as $id) {
            if ('' === $id) {
                continue;
            }
            $ids[] = (int) $id;
        }

        $entitiesToRemove = $this->customUrlRouteRepository->findBy(['id' => $ids]);
        foreach ($entitiesToRemove as $entity) {
            $this->entityManager->remove($entity);
        }
        $this->entityManager->flush();

        return $this->handleView($this->view());
    }

    public function getSecurityContext(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'Unable to get from request stack');

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->attributes->getString('webspace'));
    }

    public function getLocale(Request $request): ?string
    {
        return null;
    }
}
