<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\RootCollection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Makes collections available through a REST API.
 */
class CollectionController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface, SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var string
     */
    protected static $entityName = 'SuluMediaBundle:Collection';

    /**
     * @var string
     *
     * @deprecated Use CollectionInterface::RESOURCE_KEY instead
     */
    protected static $entityKey = CollectionInterface::RESOURCE_KEY;

    /**
     * @var ListRestHelperInterface
     */
    private $listRestHelper;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SystemCollectionManagerInterface
     */
    private $systemCollectionManager;

    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    /**
     * @var array
     */
    private $defaultCollectionType;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var string
     */
    private $collectionClass;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        ListRestHelperInterface $listRestHelper,
        SecurityCheckerInterface $securityChecker,
        TranslatorInterface $translator,
        SystemCollectionManagerInterface $systemCollectionManager,
        CollectionManagerInterface $collectionManager,
        array $defaultCollectionType,
        array $permissions,
        ?string $collectionClass = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->listRestHelper = $listRestHelper;
        $this->securityChecker = $securityChecker;
        $this->translator = $translator;
        $this->systemCollectionManager = $systemCollectionManager;
        $this->collectionManager = $collectionManager;
        $this->defaultCollectionType = $defaultCollectionType;
        $this->permissions = $permissions;
        $this->collectionClass = $collectionClass;

        if (!$this->collectionClass) {
            $this->collectionClass = CollectionEntity::class;

            @trigger_deprecation(
                'sulu/sulu',
                '2.1',
                \sprintf(
                    'Omitting the "collectionClass" argument is deprecated and will not longer work in Sulu 3.0.'
                )
            );
        }
    }

    /**
     * Shows a single collection with the given id.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        if ($this->getBooleanRequestParameter($request, 'tree', false, false)) {
            $collections = $this->collectionManager->getTreeById(
                $id,
                $this->getRequestParameter($request, 'locale', true)
            );

            return $this->handleView(
                $this->view(
                    new CollectionRepresentation($collections, CollectionInterface::RESOURCE_KEY)
                )
            );
        }

        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $depth = \intval($request->get('depth', 0));
            $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);
            $children = $this->getBooleanRequestParameter($request, 'children', false, false);

            // filter children
            $limit = $request->get('limit', null);
            $offset = $this->getOffset($request, $limit);
            $search = $this->listRestHelper->getSearchPattern();
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('sortOrder', 'ASC');

            $filter = [
                'limit' => $limit,
                'offset' => $offset,
                'search' => $search,
            ];

            $view = $this->responseGetById(
                $id,
                function($id) use ($locale, $depth, $breadcrumb, $filter, $sortBy, $sortOrder, $children) {
                    $collection = $this->collectionManager->getById(
                        $id,
                        $locale,
                        $depth,
                        $breadcrumb,
                        $filter,
                        null !== $sortBy ? [$sortBy => $sortOrder] : [],
                        $children,
                        $this->permissions[PermissionTypes::VIEW]
                    );

                    if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collection->getType()->getKey()) {
                        $this->securityChecker->checkPermission(
                            'sulu.media.system_collections',
                            PermissionTypes::VIEW
                        );
                    }

                    return $collection;
                }
            );
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        try {
            $flat = $this->getBooleanRequestParameter($request, 'flat', false);
            $depth = $request->get('depth', 0);
            $parentId = $request->get('parentId', null);
            $limit = $request->get('limit', null);
            $offset = $this->getOffset($request, $limit);
            $search = $this->listRestHelper->getSearchPattern();
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('sortOrder', 'ASC');
            $includeRoot = $this->getBooleanRequestParameter($request, 'includeRoot', false, false);

            if ('root' === $parentId) {
                $includeRoot = false;
                $parentId = null;
            }

            if ($flat) {
                $collections = $this->collectionManager->get(
                    $this->getRequestParameter($request, 'locale', true),
                    [
                        'depth' => $depth,
                        'parent' => $parentId,
                    ],
                    $limit,
                    $offset,
                    null !== $sortBy ? [$sortBy => $sortOrder] : []
                );
            } else {
                $collections = $this->collectionManager->getTree(
                    $this->getRequestParameter($request, 'locale', true),
                    $offset,
                    $limit,
                    $search,
                    $depth,
                    null !== $sortBy ? [$sortBy => $sortOrder] : [],
                    $this->securityChecker->hasPermission('sulu.media.system_collections', 'view'),
                    $this->permissions[PermissionTypes::VIEW]
                );
            }

            if ($includeRoot && !$parentId) {
                $collections = [
                    new RootCollection(
                        $this->translator->trans('sulu_media.all_collections', [], 'admin'),
                        $collections
                    ),
                ];
            }

            $all = $this->collectionManager->getCount();

            $list = new ListRepresentation(
                $collections,
                CollectionInterface::RESOURCE_KEY,
                'sulu_media.get_collections',
                $request->query->all(),
                $this->listRestHelper->getPage(),
                $this->listRestHelper->getLimit(),
                $all
            );

            $view = $this->view($list, 200);
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new collection.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing collection with the given id.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a collection with the given id.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, Request $request)
    {
        $parent = $request->get('parent');

        $this->checkSystemCollection($id, $parent);

        $delete = function($id) {
            try {
                $this->collectionManager->delete($id);
            } catch (CollectionNotFoundException $cnf) {
                throw new EntityNotFoundException(self::$entityName, $id, $cnf); // will throw 404 Entity not found
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
     *
     * @param int $id
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'move':
                    return $this->moveEntity($id, $request);
                    break;
                default:
                    throw new RestException(\sprintf('Unrecognized action: "%s"', $action));
            }
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Moves an entity into another one.
     *
     * @param int $id
     *
     * @return Response
     */
    protected function moveEntity($id, Request $request)
    {
        $destinationId = $this->getRequestParameter($request, 'destination');
        $locale = $this->getRequestParameter($request, 'locale', true);
        $collection = $this->collectionManager->move($id, $locale, $destinationId);
        $view = $this->view($collection);

        return $this->handleView($view);
    }

    /**
     * @return mixed[]
     */
    protected function getData(Request $request)
    {
        return [
            'style' => $request->get('style'),
            'type' => $request->get('type', $this->defaultCollectionType),
            'parent' => $request->get('parent'),
            'locale' => $this->getRequestParameter($request, 'locale', true),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'changer' => $request->get('changer'),
            'creator' => $request->get('creator'),
            'changed' => $request->get('changed'),
            'created' => $request->get('created'),
        ];
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        $parent = $request->get('parent');
        $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);

        $this->checkSystemCollection($id, $parent);

        try {
            $data = $this->getData($request);
            $data['id'] = $id;

            $data['locale'] = $this->getRequestParameter($request, 'locale', true);

            $collection = $this->collectionManager->save($data, $this->getUser()->getId(), $breadcrumb);

            $view = $this->view($collection, 200);
        } catch (CollectionNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    private function checkSystemCollection($id, $parent)
    {
        if ((null !== $id && $this->systemCollectionManager->isSystemCollection(\intval($id)))
            || (null !== $parent && $this->systemCollectionManager->isSystemCollection(\intval($parent)))
        ) {
            throw new AccessDeniedException('Permission "update" or "create" is not granted for system collections');
        }
    }

    /**
     * @return int
     */
    private function getOffset(Request $request, $limit)
    {
        $page = $request->get('page', 1);

        return (null !== $limit) ? $limit * ($page - 1) : 0;
    }

    /**
     * @return string
     */
    public function getSecurityContext()
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }

    public function getSecuredClass()
    {
        return $this->collectionClass;
    }

    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id') ?: $request->get('parent');
    }
}
