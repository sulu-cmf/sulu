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

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Api\RootCollection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Makes collections available through a REST API.
 */
class CollectionController extends RestController implements ClassResourceInterface, SecuredControllerInterface, SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var string
     */
    protected static $entityName = 'SuluMediaBundle:Collection';

    /**
     * @var string
     */
    protected static $entityKey = 'collections';

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

    public function __construct(
        ViewHandlerInterface $viewHandler,
        ListRestHelperInterface $listRestHelper,
        SecurityCheckerInterface $securityChecker,
        TranslatorInterface $translator,
        SystemCollectionManagerInterface $systemCollectionManager,
        CollectionManagerInterface $collectionManager
    ) {
        parent::__construct($viewHandler);

        $this->listRestHelper = $listRestHelper;
        $this->securityChecker = $securityChecker;
        $this->translator = $translator;
        $this->systemCollectionManager = $systemCollectionManager;
        $this->collectionManager = $collectionManager;
    }


    /**
     * Shows a single collection with the given id.
     *
     * @param $id
     * @param Request $request
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
                    new CollectionRepresentation($collections, 'collections')
                )
            );
        }

        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $depth = intval($request->get('depth', 0));
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
                        $children
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
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections.
     *
     * @param Request $request
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
                    $this->securityChecker->hasPermission('sulu.media.system_collections', 'view')
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
                self::$entityKey,
                'sulu_media.get_collections',
                $request->query->all(),
                $this->listRestHelper->getPage(),
                $this->listRestHelper->getLimit(),
                $all
            );

            $view = $this->view($list, 200);
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new collection.
     *
     * @param Request $request
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
     * @param int $id The id of the collection to update
     * @param Request $request
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
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
            try {
                $this->collectionManager->delete($id);
            } catch (CollectionNotFoundException $cnf) {
                throw new EntityNotFoundException(self::$entityName, $id); // will throw 404 Entity not found
            } catch (MediaException $me) {
                throw new RestException($me->getMessage(), $me->getCode()); // will throw 400 Bad Request
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
     *
     * @param int $id
     * @param Request $request
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
                    throw new RestException(sprintf('Unrecognized action: "%s"', $action));
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
     * @param Request $request
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
     * @param Request $request
     *
     * @return Collection
     */
    protected function getData(Request $request)
    {
        return [
            'style' => $request->get('style'),
            'type' => $request->get('type', $this->container->getParameter('sulu_media.collection.type.default')),
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
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        $parent = $request->get('parent');
        $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);

        if ((null !== $id && $this->systemCollectionManager->isSystemCollection(intval($id))) ||
            (null !== $parent && $this->systemCollectionManager->isSystemCollection(intval($parent)))
        ) {
            throw new AccessDeniedException('Permission "update" or "create" is not granted for system collections');
        }

        try {
            $data = $this->getData($request);
            $data['id'] = $id;

            $data['locale'] = $this->getRequestParameter($request, 'locale', true);

            $collection = $this->collectionManager->save($data, $this->getUser()->getId(), $breadcrumb);

            $view = $this->view($collection, 200);
        } catch (CollectionNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @param $limit
     *
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
        return 'sulu.media.collections';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredClass()
    {
        return CollectionEntity::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id') ?: $request->get('parent');
    }
}
