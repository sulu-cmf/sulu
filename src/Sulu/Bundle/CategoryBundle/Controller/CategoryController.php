<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Api\RootCategory;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Makes categories available through a REST API.
 */
class CategoryController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * @param class-string $categoryClass
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        private CategoryRepositoryInterface $categoryRepository,
        private TranslatorInterface $translator,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private CategoryManagerInterface $categoryManager,
        private string $categoryClass
    ) {
        parent::__construct($viewHandler);
    }

    public function getAction($id, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $findCallback = function($id) use ($locale) {
            $entity = $this->categoryManager->findById($id);

            return $this->categoryManager->getApiObject($entity, $locale);
        };

        $view = $this->responseGetById($id, $findCallback);

        return $this->handleView($view);
    }

    public function cgetAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $rootKey = $request->get('rootKey');
        $parentId = $request->get('parentId');
        $includeRoot = $this->getBooleanRequestParameter($request, 'includeRoot', false, false);

        if ('root' === $parentId) {
            $includeRoot = false;
            $parentId = null;
        }

        if ('true' == $request->get('flat')) {
            $rootId = ($rootKey) ? $this->categoryManager->findByKey($rootKey)->getId() : null;
            $expandedIds = \array_filter(\explode(',', $request->get('expandedIds', $request->get('selectedIds'))));
            $defaultSort = !$request->query->has('sortBy');
            $list = $this->getListRepresentation(
                $request,
                $locale,
                $parentId ?? $rootId,
                $expandedIds,
                $request->query->has('expandedIds'),
                $includeRoot,
                $defaultSort
            );
        } elseif ($request->query->has('ids')) {
            $ids = \array_filter(\explode(',', $request->query->get('ids')));
            $entities = $this->categoryManager->findByIds($ids);
            $categories = $this->categoryManager->getApiObjects($entities, $locale);
            $list = new CollectionRepresentation($categories, CategoryInterface::RESOURCE_KEY);
        } else {
            $entities = $this->categoryManager->findChildrenByParentKey($rootKey);
            $categories = $this->categoryManager->getApiObjects($entities, $locale);
            $list = new CollectionRepresentation($categories, CategoryInterface::RESOURCE_KEY);
        }

        return $this->handleView($this->view($list, 200));
    }

    public function postTriggerAction($id, Request $request)
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            return match ($action) {
                'move' => $this->move($id, $request),
                default => throw new RestException(\sprintf('Unrecognized action: "%s"', $action)),
            };
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    private function move($id, Request $request)
    {
        $destination = $this->getRequestParameter($request, 'destination', true);
        if ('root' === $destination) {
            $destination = null;
        }

        $category = $this->categoryManager->move($id, $destination);

        return $this->handleView($this->view(
            $this->categoryManager->getApiObject($category, $request->get('locale')))
        );
    }

    public function postAction(Request $request)
    {
        return $this->saveCategory($request);
    }

    public function putAction($id, Request $request)
    {
        return $this->saveCategory($request, $id);
    }

    public function patchAction(Request $request, $id)
    {
        return $this->saveCategory($request, $id, true);
    }

    public function deleteAction($id)
    {
        $deleteCallback = function($id) {
            $this->categoryManager->delete($id);
        };

        $view = $this->responseDelete($id, $deleteCallback);

        return $this->handleView($view);
    }

    /**
     * @return Response
     */
    protected function saveCategory(Request $request, $id = null, $patch = false)
    {
        $mediasData = $request->get('medias');
        $medias = null;
        if ($mediasData && \array_key_exists('ids', $mediasData)) {
            $medias = $mediasData['ids'];
        }

        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = [
            'id' => $id,
            'name' => (empty($request->get('name'))) ? null : $request->get('name'),
            'description' => (empty($request->get('description'))) ? null : $request->get('description'),
            'medias' => $medias,
            'key' => (empty($request->get('key'))) ? null : $request->get('key'),
            'meta' => $request->get('meta'),
            'parent' => $request->get('parentId'),
        ];
        $entity = $this->categoryManager->save($data, null, $locale, $patch);
        $category = $this->categoryManager->getApiObject($entity, $locale);

        return $this->handleView($this->view($category, 200));
    }

    protected function getListRepresentation(
        Request $request,
        $locale,
        $parentId = null,
        $expandedIds = [],
        $expandSelf = false,
        $includeRoot = false,
        bool $defaultSort = true
    ) {
        $listBuilder = $this->initializeListBuilder($locale, $defaultSort);

        // disable pagination to simplify tree handling
        $listBuilder->limit(null);

        // collect categories which children should get loaded
        $idsToExpand = [$parentId];
        if ($expandedIds) {
            $pathIds = $this->categoryRepository->findCategoryIdsBetween([$parentId], $expandedIds);
            $idsToExpand = \array_merge($idsToExpand, $pathIds);
            if ($expandSelf) {
                $idsToExpand = \array_merge($idsToExpand, $expandedIds);
            }
        }

        if ('csv' === $request->getRequestFormat()) {
            $idsToExpand = \array_filter($idsToExpand);
        }

        // generate expressions for collected parent-categories
        $parentExpressions = [];
        /** @var FieldDescriptorInterface $parentFieldDescriptor */
        $parentFieldDescriptor = $listBuilder->getFieldDescriptor('parent');
        foreach ($idsToExpand as $idToExpand) {
            $parentExpressions[] = $listBuilder->createWhereExpression(
                $parentFieldDescriptor,
                $idToExpand,
                ListBuilderInterface::WHERE_COMPARATOR_EQUAL
            );
        }

        if (!$request->get('search')) {
            // expand collected parents if search is not set
            if (\count($parentExpressions) >= 2) {
                $listBuilder->addExpression($listBuilder->createOrExpression($parentExpressions));
            } elseif (\count($parentExpressions) >= 1) {
                $listBuilder->addExpression($parentExpressions[0]);
            }
        } elseif ($request->get('search') && $parentId && !$expandedIds) {
            // filter for parentId when search is active and no expandedIds are set
            $listBuilder->addExpression($parentExpressions[0]);
        }

        $categories = $listBuilder->execute();

        foreach ($categories as &$category) {
            $category['hasChildren'] = ($category['lft'] + 1) !== $category['rgt'];

            $category['ghostLocale'] = null; // need always be set as the csv requires all columns have the same count
            if ($category['locale'] !== $locale) {
                $category['ghostLocale'] = $category['locale'];
            }
        }

        if (!empty($expandedIds)) {
            $categoriesByParentId = [];
            foreach ($categories as &$category) {
                $categoryParentId = $category['parent'];
                if (!isset($categoriesByParentId[$categoryParentId])) {
                    $categoriesByParentId[$categoryParentId] = [];
                }
                $categoriesByParentId[$categoryParentId][] = &$category;
            }

            foreach ($categories as &$category) {
                if (!isset($categoriesByParentId[$category['id']])) {
                    continue;
                }

                $category['_embedded'] = [
                    CategoryInterface::RESOURCE_KEY => $categoriesByParentId[$category['id']],
                ];
            }

            $categories = $categoriesByParentId[$parentId];
        }

        if ($includeRoot && !$parentId) {
            $categories = [
                new RootCategory(
                    $this->translator->trans('sulu_category.all_categories', [], 'admin'),
                    $categories
                ),
            ];
        }

        return new PaginatedRepresentation(
            $categories,
            CategoryInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        );
    }

    private function initializeListBuilder($locale, bool $defaultSort = false): ListBuilderInterface
    {
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(CategoryInterface::RESOURCE_KEY);

        $listBuilder = $this->listBuilderFactory->create($this->categoryClass);
        $listBuilder->setParameter('locale', $locale);
        // sort by depth before initializing listbuilder with request parameter to avoid wrong sorting in frontend
        $listBuilder->sort($fieldDescriptors['depth']);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // add default sort order
        if ($defaultSort && isset($fieldDescriptors['name'])) {
            $listBuilder->sort($fieldDescriptors['name']);
        }
        $listBuilder->addSelectField($fieldDescriptors['depth']);
        $listBuilder->addSelectField($fieldDescriptors['parent']);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['defaultLocale']);
        $listBuilder->addSelectField($fieldDescriptors['lft']);
        $listBuilder->addSelectField($fieldDescriptors['rgt']);

        return $listBuilder;
    }

    public function getSecurityContext()
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
