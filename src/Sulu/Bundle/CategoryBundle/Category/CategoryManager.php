<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryCreatedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryModifiedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryMovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRemovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryTranslationAddedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Exception\RemoveCategoryDependantResourcesFoundException;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Event\CategoryDeleteEvent;
use Sulu\Bundle\CategoryBundle\Event\CategoryEvents;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryNameMissingException;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryManager implements CategoryManagerInterface
{
    public static $categoryEntityName = CategoryInterface::class;

    public static $catTranslationEntityName = CategoryTranslationInterface::class;

    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CategoryMetaRepositoryInterface $categoryMetaRepository,
        private CategoryTranslationRepositoryInterface $categoryTranslationRepository,
        private UserRepositoryInterface $userRepository,
        private KeywordManagerInterface $keywordManager,
        private EntityManagerInterface $em,
        private EventDispatcherInterface $eventDispatcher,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TrashManagerInterface $trashManager = null
    ) {
    }

    public function findById($id)
    {
        if (!$entity = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        return $entity;
    }

    public function findByKey($key)
    {
        if (!$entity = $this->categoryRepository->findCategoryByKey($key)) {
            throw new CategoryKeyNotFoundException($key);
        }

        return $entity;
    }

    public function findByIds(array $ids)
    {
        return $this->categoryRepository->findCategoriesByIds($ids);
    }

    public function findChildrenByParentId($parentId = null)
    {
        if ($parentId && !$this->categoryRepository->isCategoryId($parentId)) {
            throw new CategoryIdNotFoundException($parentId);
        }

        return $this->categoryRepository->findChildrenCategoriesByParentId($parentId);
    }

    public function findChildrenByParentKey($parentKey = null)
    {
        if ($parentKey && !$this->categoryRepository->isCategoryKey($parentKey)) {
            throw new CategoryKeyNotFoundException($parentKey);
        }

        return $this->categoryRepository->findChildrenCategoriesByParentKey($parentKey);
    }

    /**
     * Returns category-translation or create a new one.
     *
     * @param string $locale
     *
     * @return CategoryTranslationInterface
     */
    private function findOrCreateCategoryTranslation(
        CategoryInterface $category,
        CategoryWrapper $categoryWrapper,
        $locale
    ) {
        $translationEntity = $category->findTranslationByLocale($locale);
        if (!$translationEntity) {
            $translationEntity = $this->categoryTranslationRepository->createNew();
            $translationEntity->setLocale($locale);
            $categoryWrapper->setTranslation($translationEntity);
        }

        return $translationEntity;
    }

    public function save($data, $userId, $locale, $patch = false)
    {
        $isNewCategory = !$this->getProperty($data, 'id');
        $isNewTranslation = true;

        if (!$isNewCategory) {
            $categoryEntity = $this->findById($this->getProperty($data, 'id'));

            if (false !== $categoryEntity->findTranslationByLocale($locale)) {
                $isNewTranslation = false;
            }
        } else {
            $categoryEntity = $this->categoryRepository->createNew();
        }

        // set user properties if userId is set. else these properties are set by the UserBlameSubscriber on save.
        if ($user = ($userId) ? $this->userRepository->findUserById($userId) : null) {
            if (!$categoryEntity->getCreator()) {
                $categoryEntity->setCreator($user);
            }
            $categoryEntity->setChanger($user);
        }

        $categoryWrapper = $this->getApiObject($categoryEntity, $locale);

        if (!$patch || $this->getProperty($data, 'name')) {
            $translationEntity = $this->findOrCreateCategoryTranslation($categoryEntity, $categoryWrapper, $locale);
            $translationEntity->setTranslation($this->getProperty($data, 'name', null));
        }

        if (!$patch || $this->getProperty($data, 'description')) {
            $translationEntity = $this->findOrCreateCategoryTranslation($categoryEntity, $categoryWrapper, $locale);
            $translationEntity->setDescription($this->getProperty($data, 'description', null));
        }

        if (!$patch || $this->getProperty($data, 'medias')) {
            $translationEntity = $this->findOrCreateCategoryTranslation($categoryEntity, $categoryWrapper, $locale);
            $translationEntity->setMedias(
                \array_map(
                    function($item) {
                        return $this->em->getReference(MediaInterface::class, $item);
                    },
                    $this->getProperty($data, 'medias', [])
                )
            );
        }

        $key = $this->getProperty($data, 'key');
        if (!$patch || $key) {
            $categoryWrapper->setKey($key);
        }
        if (!$patch || $this->getProperty($data, 'meta')) {
            $metaData = (\is_array($this->getProperty($data, 'meta'))) ? $this->getProperty($data, 'meta') : [];

            $metaEntities = [];
            foreach ($metaData as $meta) {
                $metaEntity = $this->categoryMetaRepository->createNew();
                $metaEntity->setId($this->getProperty($meta, 'id'));
                $metaEntity->setKey($this->getProperty($meta, 'key'));
                $metaEntity->setValue($this->getProperty($meta, 'value'));
                $metaEntity->setLocale($this->getProperty($meta, 'locale'));
                $metaEntities[] = $metaEntity;
            }
            $categoryWrapper->setMeta($metaEntities);
        }
        if (!$patch || $this->getProperty($data, 'parent')) {
            $parentCategory = null;
            if ($this->getProperty($data, 'parent')) {
                $parentCategory = $this->findById($this->getProperty($data, 'parent'));
            }
            $categoryWrapper->setParent($parentCategory);
        }

        if (!$categoryWrapper->getName()) {
            throw new CategoryNameMissingException();
        }

        $categoryEntity = $categoryWrapper->getEntity();
        $this->em->persist($categoryEntity);

        if ($isNewCategory) {
            $this->domainEventCollector->collect(new CategoryCreatedEvent($categoryEntity, $locale, $data));
        } elseif ($isNewTranslation) {
            $this->domainEventCollector->collect(new CategoryTranslationAddedEvent($categoryEntity, $locale, $data));
        } else {
            $this->domainEventCollector->collect(new CategoryModifiedEvent($categoryEntity, $locale, $data));
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new CategoryKeyNotUniqueException($key);
        }

        return $categoryEntity;
    }

    /**
     * @param array<array{id: int, resourceKey: string, depth: int}> $resources
     *
     * @return array<int, array<array{id: int, resourceKey: string}>>
     */
    private function groupResourcesByDepth(array $resources)
    {
        $grouped = [];

        foreach ($resources as $resource) {
            $depth = $resource['depth'];
            unset($resource['depth']);

            if (!isset($grouped[$depth])) {
                $grouped[$depth] = [];
            }

            $grouped[$depth][] = $resource;
        }

        \krsort($grouped);

        return \array_values($grouped);
    }

    private function checkDependantResourcesForDelete(int $id): void
    {
        $descendantCategoryResources = $this->categoryRepository->findDescendantCategoryResources($id);

        if (empty($descendantCategoryResources)) {
            return;
        }

        throw new RemoveCategoryDependantResourcesFoundException(
            [
                'id' => $id,
                'resourceKey' => CategoryInterface::RESOURCE_KEY,
            ],
            $this->groupResourcesByDepth($descendantCategoryResources),
            \count($descendantCategoryResources)
        );
    }

    public function delete($id/*, bool $forceRemoveChildren = false*/)
    {
        $forceRemoveChildren = \func_num_args() >= 2 ? (bool) \func_get_arg(1) : false;

        if (!$entity = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        if (!$forceRemoveChildren) {
            $this->checkDependantResourcesForDelete($id);
        }

        if (null !== $this->trashManager) {
            $this->trashManager->store(CategoryInterface::RESOURCE_KEY, $entity);
        }

        /** @var CategoryTranslationInterface $translation */
        foreach ($entity->getTranslations() as $translation) {
            foreach ($translation->getKeywords() as $keyword) {
                $this->keywordManager->delete($keyword, $entity);
            }
        }

        $defaultLocale = $entity->getDefaultLocale();
        $defaultTranslation = $entity->findTranslationByLocale($defaultLocale);
        $categoryName = $defaultTranslation ? $defaultTranslation->getTranslation() : null;

        $this->em->remove($entity);
        $this->domainEventCollector->collect(new CategoryRemovedEvent($id, $categoryName, $defaultLocale));
        $this->em->flush();

        // throw a category.delete event
        $event = new CategoryDeleteEvent($entity);
        $this->eventDispatcher->dispatch($event, CategoryEvents::CATEGORY_DELETE);
    }

    public function move($id, $parent)
    {
        if (!$category = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        $parentCategory = null;
        if ($parent && !$parentCategory = $this->categoryRepository->findCategoryById($parent)) {
            throw new CategoryIdNotFoundException($parent);
        }

        $previousParent = $category->getParent();
        $previousParentId = $previousParent ? $previousParent->getId() : null;
        $previousParentTranslation = $previousParent ? $this->getCategoryTranslation($previousParent) : null;
        $previousParentTitle = $previousParentTranslation ? $previousParentTranslation->getTranslation() : null;
        $previousParentTitleLocale = $previousParentTranslation ? $previousParentTranslation->getLocale() : null;

        $category->setParent($parentCategory);

        $this->domainEventCollector->collect(
            new CategoryMovedEvent(
                $category,
                $previousParentId,
                $previousParentTitle,
                $previousParentTitleLocale
            )
        );

        $this->em->flush();

        return $category;
    }

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters. If the given object is already an API-object,
     * the associated entity is used for wrapping.
     *
     * @param CategoryInterface $category
     * @param string $locale
     *
     * @return null|CategoryWrapper
     */
    public function getApiObject($category, $locale)
    {
        if ($category instanceof CategoryWrapper) {
            $category = $category->getEntity();
        }
        if (!$category instanceof CategoryInterface) {
            return;
        }

        return new CategoryWrapper($category, $locale);
    }

    /**
     * Returns an array of API-Objects for a given array of category-entities.
     * The returned array can contain null-values, if the given entities are not valid.
     *
     * @param CategoryInterface[] $entities
     * @param string $locale
     *
     * @return array
     */
    public function getApiObjects($entities, $locale)
    {
        return \array_map(
            function($entity) use ($locale) {
                return $this->getApiObject($entity, $locale);
            },
            $entities
        );
    }

    /**
     * Return the value of a key in a given data-array.
     * If the given key does not exist, the given default value is returned.
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return string|null
     */
    private function getProperty($data, $key, $default = null)
    {
        return (\array_key_exists($key, $data) && null !== $data[$key]) ? $data[$key] : $default;
    }

    private function getCategoryTranslation(CategoryInterface $category): ?CategoryTranslationInterface
    {
        return $category->findTranslationByLocale($category->getDefaultLocale()) ?: null;
    }
}
