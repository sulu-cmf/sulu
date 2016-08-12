<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\CategoryBundle\Category\CategoryListRepresentation;
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\Exception\KeyNotUniqueException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes categories available through a REST API.
 */
class CategoryController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluCategoryBundle:Category';

    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'categories';

    /**
     * {@inheritdoc}
     */
    protected $fieldsWidth = [];

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'category.category.';

    /**
     * Returns the CategoryManager.
     *
     * @return \Sulu\Bundle\CategoryBundle\Category\CategoryManager
     */
    private function getManager()
    {
        return $this->get('sulu_category.category_manager');
    }

    /**
     * Returns all fields that can be used by list.
     *
     * @Get("categories/fields")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getFieldsAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $fieldDescriptors = $this->getManager()->getFieldDescriptors($locale);
        // lft and rgt should not be made available through the API
        unset($fieldDescriptors['lft']);
        unset($fieldDescriptors['rgt']);

        // default contacts list
        return $this->handleView(
            $this->view(
                array_values(
                    array_diff_key(
                        $fieldDescriptors,
                        [
                            'depth' => false,
                            'parent' => false,
                            'hasChildren' => false,
                            'locale' => false,
                            'defaultLocale' => false,
                        ]
                    )
                ),
                200
            )
        );
    }

    /**
     * Get a single category for a given id.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $categoryManager = $this->get('sulu_category.category_manager');
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale, $categoryManager) {
                $categoryEntity = $categoryManager->findById($id);

                return $categoryManager->getApiObject($categoryEntity, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * Returns the children for a parent for the given key.
     *
     * @param Request $request
     * @param mixed $key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getChildrenAction(Request $request, $key)
    {
        if ($request->get('flat') == 'true') {
            $list = $this->getCategoryListRepresentation($request, $key);
        } else {
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('sortOrder');
            $categoryManager = $this->get('sulu_category.category_manager');
            $categories = $categoryManager->findChildren($key, $sortBy, $sortOrder);
            $wrappers = $categoryManager->getApiObjects(
                $categories,
                $this->getRequestParameter($request, 'locale', true)
            );
            $list = new CollectionRepresentation($wrappers, self::$entityKey);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Shows all categories
     * Can be filtered with "parent" and "depth" parameters.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('flat') == 'true') {
            $list = $this->getCategoryListRepresentation($request);
        } else {
            $parent = $request->get('parent');
            $depth = $request->get('depth');
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('sortOrder');
            $categoryManager = $this->get('sulu_category.category_manager');
            $categories = $categoryManager->find($parent, $depth, $sortBy, $sortOrder);
            $wrappers = $categoryManager->getApiObjects(
                $categories,
                $this->getRequestParameter($request, 'locale', true)
            );
            $list = new CollectionRepresentation($wrappers, self::$entityKey);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Adds a new category.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity($request, null);
    }

    /**
     * Changes an existing category.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        try {
            if (!$request->get('name')) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            return $this->saveEntity($request, $id);
        } catch (MissingArgumentException $exc) {
            $view = $this->view($exc->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Partly changes an existing category.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $id)
    {
        return $this->saveEntity($request, $id);
    }

    /**
     * Deletes the category for the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $categoryManager = $this->get('sulu_category.category_manager');
            $categoryManager->delete($id);
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @return RestHelperInterface
     */
    protected function getRestHelper()
    {
        return $this->get('sulu_core.doctrine_rest_helper');
    }

    /**
     * Handles the change of a category. Used in PUT and PATCH.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity(Request $request, $id)
    {
        try {
            $categoryManager = $this->get('sulu_category.category_manager');
            $key = $request->get('key');
            $locale = $this->getRequestParameter($request, 'locale', true);
            $data = [
                'id' => $id,
                'key' => (empty($key)) ? null : $key,
                'name' => $request->get('name'),
                'meta' => $request->get('meta'),
                'parent' => $request->get('parent'),
                'locale' => $locale,
            ];
            $categoryEntity = $categoryManager->save($data, $this->getUser()->getId());
            $categoryWrapper = $categoryManager->getApiObject($categoryEntity, $locale);

            $view = $this->view($categoryWrapper, 200);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (KeyNotUniqueException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a Category-list-representation.
     *
     * @param Request $request
     *
     * @throws MissingParameterException iff the locale is missing in the request
     *
     * @return CategoryListRepresentation
     */
    protected function getCategoryListRepresentation(Request $request, $parentKey = null)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->getRestHelper();

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $listBuilder = $factory->create(self::$entityName);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $fieldDescriptors = $this->getManager()->getFieldDescriptors($locale);
        $listBuilder->sort($fieldDescriptors['depth']);

        $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listBuilder->addSelectField($fieldDescriptors['depth']);
        $listBuilder->addSelectField($fieldDescriptors['parent']);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['defaultLocale']);
        $listBuilder->addSelectField($fieldDescriptors['lft']);
        $listBuilder->addSelectField($fieldDescriptors['rgt']);

        if ($parentKey !== null) {
            $this->addParentSelector($parentKey, $listBuilder);
        }

        // FIXME: don't do this.
        $listBuilder->limit(null);

        $results = $listBuilder->execute();
        foreach ($results as &$result) {
            $result['hasChildren'] = ($result['lft'] + 1) !== $result['rgt'];
        }
        unset($result); // break the reference

        $list = new CategoryListRepresentation(
            $results,
            self::$entityKey,
            'get_categories',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $list;
    }

    /**
     * append parent selector to listbuilder.
     *
     * @param $parentKey
     * @param DoctrineListBuilder $listBuilder
     */
    protected function addParentSelector($parentKey, DoctrineListBuilder $listBuilder)
    {
        $manager = $this->getManager();
        $parentEntity = $manager->findByKey($parentKey);

        $listBuilder->between(
            new DoctrineFieldDescriptor(
                'lft',
                'lft',
                CategoryManager::$categoryEntityName,
                'public.lft',
                [],
                true
            ),
            [$parentEntity->getLft() + 1, $parentEntity->getRgt()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.settings.categories';
    }
}
