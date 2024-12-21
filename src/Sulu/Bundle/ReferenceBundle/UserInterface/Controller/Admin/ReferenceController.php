<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\UserInterface\Controller\Admin;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @final
 *
 * @internal there should be no need to extend this class
 */
class ReferenceController extends AbstractRestController implements ClassResourceInterface
{
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
        private SecurityCheckerInterface $securityChecker,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $this->securityChecker->checkPermission(
            ReferenceAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        /** @var string|null $resourceId */
        $resourceId = $request->query->get('resourceId');
        /** @var string|null $resourceKey */
        $resourceKey = $request->query->get('resourceKey');
        $parentId = $request->query->get('parentId');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $limit;

        $referenceResourceKey = null;
        $referenceResourceId = null;
        $referenceLocale = $request->query->get('locale');

        $rootLevel = true;
        if ($parentId) {
            $rootLevel = false;
            list(
                $referenceResourceKey,
                $referenceResourceId,
                $referenceLocale,
            ) = \explode('__', $parentId); // see expandItems method about the implode
            $limit = null;
            $offset = null;
        }

        $filters = \array_filter([
            'resourceId' => $resourceId,
            'resourceKey' => $resourceKey,
            'referenceResourceKey' => $referenceResourceKey,
            'referenceResourceId' => $referenceResourceId,
            'referenceLocale' => $referenceLocale,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $fields = \explode(',', $request->query->get('fields', ''));
        $removeFields = ['id']; // the frontend always add the id field, but we don't need it in this case as we group by other fields
        $fields = [
            ...$fields,
            'referenceResourceKey',
            'referenceResourceId',
            'referenceLocale',
            'referenceRouterAttributes',
        ];

        $sortBys = [];
        /** @var string|null $sortBy */
        $sortBy = $request->query->get('sortBy');
        if (\in_array($sortBy, $fields, true)) {
            /** @var 'asc'|'desc' $sortOrder */
            $sortOrder = $request->query->get('sortOrder', 'asc');
            $sortBys = [$sortBy => $sortOrder];
        }

        if ($rootLevel) {
            $removeFields[] = 'referenceContext';
            $removeFields[] = 'referenceProperty';
        }

        $fields = \array_filter(\array_unique(\array_diff($fields, $removeFields)));
        $rows = $this->expandItems(
            $this->referenceRepository->findFlatBy($filters, $sortBys, $fields, distinct: $rootLevel),
            $rootLevel
        );
        $total = $this->referenceRepository->count($filters, $rootLevel ? $fields : []);

        $listRepresentation = $limit
            ? new PaginatedRepresentation(
                $rows,
                ReferenceInterface::RESOURCE_KEY,
                $page,
                $limit,
                $total
            ) : new CollectionRepresentation(
                $rows,
                ReferenceInterface::RESOURCE_KEY
            )
        ;

        return $this->handleView(
            $this->view($listRepresentation, 200)
        );
    }

    /**
     * @param iterable<array<string, mixed>> $rows
     *
     * @return iterable<array<string, mixed>>
     */
    private function expandItems(iterable $rows, bool $rootLevel): iterable
    {
        $count = 0;
        foreach ($rows as $row) {
            // without a unique identifier the frontend currently has a problem with the list component
            $row['id'] = \implode('__', [$row['referenceResourceKey'], $row['referenceResourceId'], $row['referenceLocale']]);

            if ($rootLevel) {
                $row['hasChildren'] = $rootLevel;
            }

            if (!$rootLevel) {
                $row['id'] .= '__' . (++$count);
                unset($row['referenceResourceKey']);
                unset($row['referenceResourceId']);
                unset($row['referenceLocale']);
                unset($row['referenceTitle']);
                unset($row['resourceViewAttributes']);
            }

            yield $row;
        }
    }
}
