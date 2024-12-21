<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Collection\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionMovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionTranslationAddedEvent;
use Sulu\Bundle\MediaBundle\Domain\Exception\RemoveCollectionDependantResourcesFoundException;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionTypeNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Default implementation of collection manager.
 */
class CollectionManager implements CollectionManagerInterface
{
    private static $entityName = \Sulu\Bundle\MediaBundle\Entity\Collection::class;

    private static $entityCollectionType = \Sulu\Bundle\MediaBundle\Entity\Collection::class;

    private static $entityCollectionMeta = \Sulu\Bundle\MediaBundle\Entity\CollectionMeta::class;

    private static $entityUser = UserInterface::class;

    private static $entityContact = ContactInterface::class;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    private $fieldDescriptors;

    /**
     * @var int
     */
    private $count;

    /**
     * @param string $collectionPreviewFormat;
     * @param array<string, int> $permissions;
     */
    public function __construct(
        private CollectionRepositoryInterface $collectionRepository,
        private MediaRepositoryInterface $mediaRepository,
        private FormatManagerInterface $formatManager,
        private UserRepositoryInterface $userRepository,
        private EntityManager $em,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TokenStorageInterface $tokenStorage,
        private ?TrashManagerInterface $trashManager,
        private $collectionPreviewFormat,
        private $permissions
    ) {
    }

    public function getById(
        $id,
        $locale,
        $depth = 0,
        $breadcrumb = false,
        $filter = [],
        $sortBy = [],
        $children = false,
        $permission = null
    ) {
        $collectionEntity = $this->collectionRepository->findCollectionById($id);
        if (null === $collectionEntity) {
            throw new CollectionNotFoundException($id);
        }
        $filter['locale'] = $locale;
        $collectionChildren = null;
        if ($children) {
            $collectionChildren = $this->collectionRepository->findCollectionSet(
                $depth,
                $filter,
                $collectionEntity,
                $sortBy,
                $this->getCurrentUser(),
                $permission
            );
        }

        $breadcrumbEntities = null;
        if ($breadcrumb) {
            $breadcrumbEntities = $this->collectionRepository->findCollectionBreadcrumbById($id);
        }

        return $this->getApiEntity($collectionEntity, $locale, $collectionChildren, $breadcrumbEntities);
    }

    public function get($locale, $filter = [], $limit = null, $offset = null, $sortBy = [])
    {
        $collectionEntities = $this->collectionRepository->findCollections($filter, $limit, $offset, $sortBy);
        $this->count = $collectionEntities instanceof Paginator ?
            $collectionEntities->count() : \count($collectionEntities);

        $collections = [];
        foreach ($collectionEntities as $entity) {
            $collections[] = $this->getApiEntity($entity, $locale);
        }

        return $collections;
    }

    public function getByKey($key, $locale)
    {
        $collection = $this->collectionRepository->findCollectionByKey($key);

        if (!$collection) {
            return;
        }

        return $this->getApiEntity($collection, $locale);
    }

    public function getTreeById($id, $locale)
    {
        $collectionSet = $this->collectionRepository->findTree($id, $locale);

        /** @var Collection[] $collections sorted by id */
        $collections = [];
        /** @var Collection[] $result collections without parent */
        $result = [];
        foreach ($collectionSet as $collection) {
            $apiEntity = new Collection($collection, $locale);
            $this->addPreview($apiEntity);

            $collections[$collection->getId()] = $apiEntity;

            if (null !== $collection->getParent()) {
                $collections[$collection->getParent()->getId()]->addChild($apiEntity);
            } else {
                $result[] = $apiEntity;
            }
        }

        return $result;
    }

    public function getTree(
        $locale,
        $offset,
        $limit,
        $search,
        $depth = 0,
        $sortBy = [],
        $systemCollections = true,
        $permission = null
    ) {
        $filter = [
            'offset' => $offset,
            'limit' => $limit,
            'search' => $search,
            'locale' => $locale,
            'systemCollections' => $systemCollections,
        ];

        /** @var CollectionEntity[] $entities */
        $entities = $this->collectionRepository->findCollectionSet(
            $depth,
            $filter,
            null,
            $sortBy,
            $this->getCurrentUser(),
            $permission
        );

        $collections = [];
        foreach ($entities as $entity) {
            if (null === $entity->getParent()) {
                $collections[] = $this->getApiEntity($entity, $locale, $entities);
            }
        }

        unset($filter['limit']);
        unset($filter['offset']);
        $this->count = $this->collectionRepository->countCollections($depth, $filter);

        return $collections;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return DoctrineFieldDescriptor[]
     */
    private function initializeFieldDescriptors()
    {
        $fieldDescriptors = [];

        $this->fieldDescriptors = $fieldDescriptors;

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            'id',
            [],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            'string',
            '50px'
        );
        $this->fieldDescriptors['type_name'] = new DoctrineFieldDescriptor(
            'name',
            'type_name',
            self::$entityCollectionType,
            'locale',
            [
                self::$entityCollectionType => new DoctrineJoinDescriptor(
                    self::$entityCollectionType,
                    self::$entityName . '.type'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO
        );
        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$entityCollectionMeta,
            'title',
            [
                self::$entityName => new DoctrineJoinDescriptor(
                    self::$entityCollectionMeta,
                    self::$entityName . '.meta'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            'title',
            '50px'
        );
        $this->fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::$entityCollectionMeta,
            'description',
            [
                self::$entityName => new DoctrineJoinDescriptor(
                    self::$entityCollectionMeta,
                    self::$entityName . '.meta'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            'description'
        );
        $this->fieldDescriptors['changer'] = new DoctrineFieldDescriptor(
            'firstname',
            'changer',
            self::$entityContact,
            'changer',
            [
                self::$entityUser => new DoctrineJoinDescriptor(
                    self::$entityUser,
                    self::$entityName . '.changer'
                ),
                self::$entityContact => new DoctrineJoinDescriptor(
                    self::$entityContact,
                    self::$entityUser . '.contact'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );
        $this->fieldDescriptors['creator'] = new DoctrineFieldDescriptor(
            'firstname',
            'creator',
            self::$entityContact,
            'creator',
            [
                self::$entityUser => new DoctrineJoinDescriptor(
                    self::$entityUser,
                    self::$entityName . '.creator'
                ),
                self::$entityContact => new DoctrineJoinDescriptor(
                    self::$entityContact,
                    self::$entityUser . '.contact'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );
        $this->fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'thumbnails',
            'thumbnails',
            self::$entityName,
            'thumbnails',
            [],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            'thumbnails'
        );

        return $this->fieldDescriptors;
    }

    public function getFieldDescriptors()
    {
        if (null === $this->fieldDescriptors) {
            $this->initializeFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    public function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }

    public function save(array $data, ?int $userId = null, bool $breadcrumb = false)
    {
        if (isset($data['id'])) {
            $collection = $this->modifyCollection($data, $this->getUser($userId), $breadcrumb);
        } else {
            $collection = $this->createCollection($data, $this->getUser($userId), $breadcrumb);
        }

        if ($breadcrumb) {
            $breadcrumbEntities = $this->collectionRepository->findCollectionBreadcrumbById($collection->getId());
            $this->setBreadcrumbToCollection($collection, $data['locale'], $breadcrumbEntities);
        }

        return $collection;
    }

    private function modifyCollection($data, $user, $breadcrumb): Collection
    {
        $locale = $data['locale'];
        $collection = $this->getById($data['id'], $locale);
        $data['changer'] = $user;
        $data['changed'] = new \DateTime();

        /** @var CollectionInterface $collectionEntity */
        $collectionEntity = $collection->getEntity();
        $isNewLocale = true;

        foreach ($collectionEntity->getMeta() as $meta) {
            if ($meta->getLocale() === $locale) {
                $isNewLocale = false;

                break;
            }
        }

        $collection = $this->setDataToCollection(
            $collection,
            $data
        );

        $this->em->persist($collectionEntity);

        $isSystemCollection = 2 === $collection->getType()->getId();

        if ($isNewLocale) {
            $this->domainEventCollector->collect(
                new CollectionTranslationAddedEvent($collectionEntity, $collection->getLocale(), $data)
            );
        } elseif (!$isSystemCollection) {
            // do not dispatch modify event for system collections because the current implementation of the SystemCollectionManager
            // triggers this code for every locale each time the cache of the project is cleared
            $this->domainEventCollector->collect(
                new CollectionModifiedEvent($collectionEntity, $collection->getLocale(), $data)
            );
        }

        $this->em->flush();

        return $collection;
    }

    private function createCollection($data, $user, $breadcrumb): Collection
    {
        $data['changer'] = $user;
        $data['creator'] = $user;
        $data['changed'] = new \DateTime();
        $data['created'] = new \DateTime();

        $collectionEntity = new CollectionEntity();
        $collection = $this->getApiEntity($collectionEntity, $data['locale']);

        $collection = $this->setDataToCollection($collection, $data);

        /** @var CollectionEntity $collectionEntity */
        $collectionEntity = $collection->getEntity();
        $collectionEntity->setDefaultMeta($collectionEntity->getMeta()->first() ?: null);

        $this->em->persist($collectionEntity);

        $this->domainEventCollector->collect(
            new CollectionCreatedEvent($collectionEntity, $collection->getLocale(), $data)
        );

        $this->em->flush();

        return $collection;
    }

    /**
     * Data can be set over by array.
     *
     * @param array $data
     *
     * @return Collection
     */
    protected function setDataToCollection(Collection $collection, $data)
    {
        // set parent
        if (!empty($data['parent'])) {
            $collectionEntity = $this->collectionRepository->findCollectionById($data['parent']);
            $collection->setParent($this->getApiEntity($collectionEntity, $data['locale']));
        }

        // set other data
        foreach ($data as $attribute => $value) {
            if ($value) {
                switch ($attribute) {
                    case 'title':
                        $collection->setTitle($value);
                        break;
                    case 'key':
                        $collection->setKey($value);
                        break;
                    case 'description':
                        $collection->setDescription($value);
                        break;
                    case 'style':
                        $collection->setStyle($value);
                        break;
                    case 'type':
                        if (!isset($value['id'])) {
                            break;
                        }
                        $type = $this->getTypeById($value['id']);
                        $collection->setType($type);
                        break;
                    case 'changer':
                        $collection->setChanger($value);
                        break;
                    case 'creator':
                        $collection->setCreator($value);
                        break;
                    case 'properties':
                        $collection->setProperties($value);
                        break;
                }
            }
        }

        return $collection;
    }

    /**
     * Sets the given breadcrumb entities as breadcrum on the given collection.
     *
     * @return Collection
     */
    protected function setBreadcrumbToCollection(Collection $collection, $locale, $breadcrumbEntities)
    {
        $breadcrumbApiEntities = [];
        foreach ($breadcrumbEntities as $entity) {
            $breadcrumbApiEntities[] = $this->getApiEntity($entity, $locale);
        }
        $collection->setBreadcrumb($breadcrumbApiEntities);

        return $collection;
    }

    /**
     * @param int $typeId
     *
     * @return CollectionType
     *
     * @throws CollectionTypeNotFoundException
     */
    protected function getTypeById($typeId)
    {
        /** @var CollectionType $type */
        $type = $this->em->getRepository(CollectionType::class)->find($typeId);
        if (!$type) {
            throw new CollectionTypeNotFoundException('Collection Type with the ID ' . $typeId . ' not found');
        }

        return $type;
    }

    private function checkDescendantPermissionsForDelete(int $id): void
    {
        $user = $this->getCurrentUser();

        if (null === $user) {
            return;
        }

        $unauthorizedDescendantCollectionsCount = $this->collectionRepository->countUnauthorizedDescendantCollections(
            $id,
            $user,
            $this->permissions[PermissionTypes::DELETE]
        );

        if (!$unauthorizedDescendantCollectionsCount) {
            return;
        }

        throw new InsufficientDescendantPermissionsException(
            $unauthorizedDescendantCollectionsCount,
            PermissionTypes::DELETE
        );
    }

    private function checkDependantResourcesForDelete(int $id): void
    {
        $descendantResources = $this->findAllDescendantResources($id);

        if (empty($descendantResources)) {
            return;
        }

        $descendantResourcesCount = $this->countGroupedResources($descendantResources);

        throw new RemoveCollectionDependantResourcesFoundException(
            [
                'id' => $id,
                'resourceKey' => CollectionInterface::RESOURCE_KEY,
            ],
            $descendantResources,
            $descendantResourcesCount
        );
    }

    public function delete($id/*, bool $forceRemoveChildren = false*/)
    {
        $forceRemoveChildren = \func_num_args() >= 2 ? (bool) \func_get_arg(1) : false;

        $collectionEntity = $this->collectionRepository->findCollectionById($id);

        if (!$collectionEntity) {
            throw new CollectionNotFoundException($id);
        }

        if ($this->trashManager) {
            $this->trashManager->store(CollectionInterface::RESOURCE_KEY, $collectionEntity);
        }

        $collectionId = $collectionEntity->getId();
        /** @var CollectionMeta|null $collectionMeta */
        $collectionMeta = $collectionEntity->getDefaultMeta();
        $collectionTitle = $collectionMeta ? $collectionMeta->getTitle() : null;
        $locale = $collectionMeta ? $collectionMeta->getLocale() : null;

        if (!$forceRemoveChildren) {
            $this->checkDescendantPermissionsForDelete($id);
            $this->checkDependantResourcesForDelete($id);
        }

        $this->em->remove($collectionEntity);

        foreach ($collectionEntity->getMeta() as $meta) {
            $this->em->remove($meta);
        }

        $this->domainEventCollector->collect(
            new CollectionRemovedEvent($collectionId, $collectionTitle, $locale)
        );

        $this->em->flush();
    }

    public function move($id, $locale, $destinationId = null)
    {
        $collectionEntity = $this->collectionRepository->findCollectionById($id);

        if (null === $collectionEntity) {
            throw new CollectionNotFoundException($id);
        }

        $previousParent = $collectionEntity->getParent();
        $previousParentId = $previousParent ? $previousParent->getId() : null;
        $previousParentMeta = $previousParent ? $this->getCollectionMeta($previousParent, $locale) : null;
        $previousParentTitle = $previousParentMeta ? $previousParentMeta->getTitle() : null;
        $previousParentTitleLocale = $previousParentMeta ? $previousParentMeta->getLocale() : null;

        $destinationEntity = null;
        if (null !== $destinationId) {
            $destinationEntity = $this->collectionRepository->findCollectionById($destinationId);
        }

        $collectionEntity->setParent($destinationEntity);

        $this->domainEventCollector->collect(
            new CollectionMovedEvent(
                $collectionEntity,
                $previousParentId,
                $previousParentTitle,
                $previousParentTitleLocale
            )
        );

        $this->em->flush();

        return $this->getApiEntity($collectionEntity, $locale);
    }

    /**
     * Returns a user for a given user-id.
     *
     * @param int $userId
     *
     * @return UserInterface|null
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * @return Collection
     */
    protected function addPreview(Collection $collection)
    {
        return $collection->setPreview(
            $this->getPreview($collection->getId(), $collection->getLocale())
        );
    }

    /**
     * @param int $id
     * @param string $locale
     *
     * @return array
     */
    protected function getPreview($id, $locale)
    {
        $medias = $this->mediaRepository
            ->findMedia(['collection' => $id, 'paginator' => false], 1);

        if (\count($medias) > 0) {
            $media = $medias[0];
            foreach ($media->getFiles() as $file) {
                foreach ($file->getFileVersions() as $fileVersion) {
                    if ($fileVersion->getVersion() == $file->getVersion()) {
                        $format = $this->getPreviewsFromFileVersion($media->getId(), $fileVersion, $locale);

                        if (!empty($format)) {
                            return $format;
                        }
                    }
                }
            }
        }

        return;
    }

    /**
     * @param int $mediaId
     * @param FileVersion $fileVersion
     * @param string $locale
     *
     * @return array
     */
    protected function getPreviewsFromFileVersion($mediaId, $fileVersion, $locale)
    {
        $title = '';
        /*
         * @var FileVersionMeta
         */
        foreach ($fileVersion->getMeta() as $key => $meta) {
            if ($meta->getLocale() == $locale) {
                $title = $meta->getTitle();
                break;
            } elseif (0 == $key) { // fallback title
                $title = $meta->getTitle();
            }
        }

        $mediaFormats = $this->formatManager->getFormats(
            $mediaId,
            $fileVersion->getName(),
            $fileVersion->getVersion(),
            $fileVersion->getSubVersion(),
            $fileVersion->getMimeType()
        );

        foreach ($mediaFormats as $formatName => $formatUrl) {
            if ($formatName == $this->collectionPreviewFormat) {
                return [
                    'url' => $formatUrl,
                    'title' => $title,
                ];
            }
        }

        return [];
    }

    /**
     * Prepare an api entity.
     *
     * @param string $locale
     * @param CollectionEntity[] $entities nested set
     * @param array $breadcrumbEntities
     *
     * @return Collection
     */
    protected function getApiEntity(CollectionInterface $entity, $locale, $entities = null, $breadcrumbEntities = null)
    {
        $apiEntity = new Collection($entity, $locale);

        $children = null;

        if (null !== $entities) {
            $children = [];
            foreach ($entities as $possibleChild) {
                if (null !== ($parent = $possibleChild->getParent()) && $parent->getId() === $entity->getId()) {
                    $children[] = $this->getApiEntity($possibleChild, $locale, $entities);
                }
            }
        }

        $apiEntity->setChildren($children);
        if (null !== $entity->getParent()) {
            $apiEntity->setParent($this->getApiEntity($entity->getParent(), $locale));
        }

        if (null !== $breadcrumbEntities) {
            $this->setBreadcrumbToCollection($apiEntity, $locale, $breadcrumbEntities);
        }

        if ($entity && $entity->getId()) {
            $apiEntity->setMediaCount($this->collectionRepository->countMedia($entity));
            $apiEntity->setSubCollectionCount($this->collectionRepository->countSubCollections($entity));
        }

        return $this->addPreview($apiEntity);
    }

    /**
     * Returns the current user from the token storage.
     *
     * @return UserInterface|null
     */
    protected function getCurrentUser()
    {
        if ($this->tokenStorage && ($token = $this->tokenStorage->getToken())) {
            $user = $token->getUser();

            if ($user instanceof UserInterface) {
                return $user;
            }
        }

        return null;
    }

    private function getCollectionMeta(CollectionInterface $collection, ?string $locale): ?CollectionMeta
    {
        /** @var CollectionMeta|null $meta */
        $meta = $collection->getDefaultMeta();
        foreach ($collection->getMeta() as $collectionMeta) {
            if ($collectionMeta->getLocale() === $locale) {
                return $collectionMeta;
            }
        }

        return $meta;
    }

    /**
     * @param array<array{id: int, resourceKey: string, depth: int}> $resources
     *
     * @return array<int, array<array{id: int, resourceKey: string}>>
     */
    private function groupResourcesByDepth(array $resources): array
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

    /**
     * @return array<int, array<array{id: int, resourceKey: string}>>
     */
    private function findAllDescendantResources(int $collectionId): array
    {
        $descendantCollections = $this->collectionRepository->findDescendantCollectionResources($collectionId);
        $descendantMedia = $this->mediaRepository->findMediaResourcesByCollection($collectionId, true);

        $result = \array_merge($descendantCollections, $descendantMedia);

        return $this->groupResourcesByDepth($result);
    }

    /**
     * @param array<int, array<array{id: int, resourceKey: string}>> $groupedResources
     */
    private function countGroupedResources(array $groupedResources): int
    {
        $counter = 0;

        /** @var array<array{id: int, resourceKey: string}> $resources */
        foreach ($groupedResources as $resources) {
            $counter += \count($resources);
        }

        return $counter;
    }
}
