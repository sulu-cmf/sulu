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

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Category\KeywordManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\KeywordIsMultipleReferencedException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordNotUniqueException;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides keywords for categories.
 */
class KeywordController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    public const FORCE_OVERWRITE = 'overwrite';

    public const FORCE_DETACH = 'detach';

    public const FORCE_MERGE = 'merge';

    protected static $entityKey = 'category_keywords';

    /**
     * @param class-string $keywordClass
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private KeywordManagerInterface $keywordManager,
        private KeywordRepositoryInterface $keywordRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private EntityManagerInterface $entityManager,
        private string $keywordClass
    ) {
        parent::__construct($viewHandler);
        $this->restHelper = $restHelper;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->keywordManager = $keywordManager;
        $this->keywordRepository = $keywordRepository;
        $this->categoryRepository = $categoryRepository;
        $this->entityManager = $entityManager;
        $this->keywordClass = $keywordClass;
    }

    /**
     * Returns list of keywords filtered by the category.
     *
     * @param int $categoryId
     *
     * @return Response
     */
    public function cgetAction($categoryId, Request $request)
    {
        /** @var CategoryInterface $category */
        $category = $this->categoryRepository->find($categoryId);

        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('category_keywords');

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($this->keywordClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptor);

        /** @var string $locale */
        $locale = $request->get('locale');
        $categoryTranslation = $category->findTranslationByLocale($locale);

        if (false == $categoryTranslation) {
            return $this->handleView($this->view(null, 404));
        }

        $listBuilder->where($fieldDescriptor['locale'], $locale);
        $listBuilder->where(
            $fieldDescriptor['categoryTranslationIds'],
            $categoryTranslation
        );

        // should eliminate duplicates
        $listBuilder->distinct(true);
        $listBuilder->addGroupBy($fieldDescriptor['id']);

        $listResponse = $listBuilder->execute();

        $list = new PaginatedRepresentation(
            $listResponse,
            self::$entityKey,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Creates new keyword for given category.
     *
     * @param int $categoryId
     *
     * @return Response
     */
    public function postAction($categoryId, Request $request)
    {
        /** @var KeywordInterface $keyword */
        $keyword = $this->keywordRepository->createNew();
        $category = $this->categoryRepository->findCategoryById($categoryId);
        $keyword->setKeyword($request->get('keyword'));
        $keyword->setLocale($request->get('locale'));

        $keyword = $this->keywordManager->save($keyword, $category);

        $this->entityManager->persist($keyword);
        $this->entityManager->flush();

        return $this->handleView($this->view($keyword));
    }

    public function getAction($categoryId, $id, Request $request)
    {
        $keyword = $this->keywordRepository->findById($id);

        if (!$keyword) {
            return $this->handleView($this->view(null, 404));
        }

        return $this->handleView($this->view($keyword));
    }

    /**
     * @throws KeywordIsMultipleReferencedException
     * @throws KeywordNotUniqueException
     */
    public function putAction($categoryId, $id, Request $request)
    {
        $keyword = $this->keywordRepository->findById($id);

        if (!$keyword) {
            return $this->handleView($this->view(null, 404));
        }

        $force = $request->get('force');
        $category = $this->categoryRepository->findCategoryById($categoryId);
        $keyword->setKeyword($request->get('keyword'));

        $keyword = $this->keywordManager->save($keyword, $category, $force);

        $this->entityManager->persist($keyword);
        $this->entityManager->flush();

        return $this->handleView($this->view($keyword));
    }

    /**
     * Delete given keyword from given category.
     *
     * @param int $categoryId
     *
     * @return Response
     */
    public function deleteAction($categoryId, $id)
    {
        $keyword = $this->keywordRepository->findById($id);
        $category = $this->categoryRepository->findCategoryById($categoryId);
        $this->keywordManager->delete($keyword, $category);

        $this->entityManager->flush();

        return $this->handleView($this->view());
    }

    /**
     * Delete given keyword from given category.
     *
     * @param int $categoryId
     *
     * @return Response
     */
    public function cdeleteAction($categoryId, Request $request)
    {
        $category = $this->categoryRepository->findCategoryById($categoryId);

        $ids = \array_filter(\explode(',', $request->get('ids')));
        foreach ($ids as $id) {
            $keyword = $this->keywordRepository->findById($id);
            $this->keywordManager->delete($keyword, $category);
        }

        $this->entityManager->flush();

        return $this->handleView($this->view());
    }

    public function getSecurityContext()
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
