<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityRepositoryTrait;

/**
 * @extends EntityRepository<MediaInterface>
 */
class MediaRepository extends EntityRepository implements MediaRepositoryInterface
{
    use SecuredEntityRepositoryTrait;

    /**
     * @var AccessControlQueryEnhancer|null
     */
    private $accessControlQueryEnhancer;

    public function findMediaById($id, $asArray = false)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('media')
                ->leftJoin('media.type', 'type')
                ->leftJoin('media.collection', 'collection')
                ->leftJoin('media.files', 'file')
                ->leftJoin('file.fileVersions', 'fileVersion')
                ->leftJoin('fileVersion.formatOptions', 'formatOptions')
                ->leftJoin('fileVersion.meta', 'fileVersionMeta')
                ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
                ->leftJoin('fileVersion.contentLanguages', 'fileVersionContentLanguage')
                ->leftJoin('fileVersion.publishLanguages', 'fileVersionPublishLanguage')
                ->leftJoin('media.creator', 'creator')
                ->leftJoin('creator.contact', 'creatorContact')
                ->leftJoin('media.changer', 'changer')
                ->leftJoin('changer.contact', 'changerContact')
                ->leftJoin('media.previewImage', 'previewImage')
                ->addSelect('type')
                ->addSelect('collection')
                ->addSelect('file')
                ->addSelect('fileVersion')
                ->addSelect('formatOptions')
                ->addSelect('fileVersionMeta')
                ->addSelect('fileVersionDefaultMeta')
                ->addSelect('fileVersionContentLanguage')
                ->addSelect('fileVersionPublishLanguage')
                ->addSelect('creator')
                ->addSelect('changer')
                ->addSelect('creatorContact')
                ->addSelect('changerContact')
                ->addSelect('previewImage')
                ->where('media.id = :mediaId');

            $query = $queryBuilder->getQuery();
            $query->setParameter('mediaId', $id);

            if ($asArray) {
                /** @var MediaInterface[] $result */
                $result = $query->getArrayResult();

                if (isset($result[0])) {
                    return $result[0];
                } else {
                    return null;
                }
            } else {
                /** @var MediaInterface */
                return $query->getSingleResult();
            }
        } catch (NoResultException $ex) {
            return null;
        }
    }

    public function findMediaByIdForRendering($id, $formatKey /*, $version = null */)
    {
        $version = \func_num_args() > 2 ? \func_get_arg(2) : null;

        try {
            $queryBuilder = $this->createQueryBuilder('media')
                ->leftJoin('media.files', 'file')
                ->addSelect('file')
                ->where('media.id = :mediaId')
                ->setParameter('mediaId', $id);

            $fileVersionJoinCondition = 'file.version = fileVersion.version';
            if (null !== $version) {
                $fileVersionJoinCondition = 'fileVersion.version IN (:version, fileVersion.version)'; // for the x-robots canonical we require latest version and old version
                $queryBuilder->setParameter('version', $version);
            }

            $queryBuilder
                ->addSelect('fileVersion')
                ->leftJoin('file.fileVersions', 'fileVersion', Join::WITH, $fileVersionJoinCondition);

            if (null !== $formatKey) {
                $queryBuilder
                    ->addSelect('formatOptions')
                    ->leftJoin(
                        'fileVersion.formatOptions',
                        'formatOptions',
                        Join::WITH,
                        'formatOptions.formatKey = :formatKey'
                    )
                    ->setParameter('formatKey', $formatKey);
            }

            /** @var MediaInterface */
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    public function findMedia(
        $filter = [],
        $limit = null,
        $offset = null,
        ?UserInterface $user = null,
        $permission = null
    ) {
        list(
            $collection,
            $systemCollections,
            $types,
            $search,
            $orderBy,
            $orderSort,
            $ids,
        ) = $this->extractFilterVars($filter);

        // if empty array of ids is requested return empty array of medias
        if (null !== $ids && 0 === \count($ids)) {
            return [];
        }

        if (empty($orderBy)) {
            $orderBy = 'media.id';
            $orderSort = 'asc';
        }

        if (!$ids) {
            $ids = $this->getIds(
                $collection,
                $systemCollections,
                $types,
                $search,
                $orderBy,
                $orderSort,
                $limit,
                $offset
            );
        }

        $queryBuilder = $this->createQueryBuilder('media')
            ->leftJoin('media.type', 'type')
            ->leftJoin('media.collection', 'collection')
            ->innerJoin('media.files', 'file')
            ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
            ->leftJoin('fileVersion.tags', 'tag')
            ->leftJoin('fileVersion.meta', 'fileVersionMeta')
            ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
            ->leftJoin('fileVersion.contentLanguages', 'fileVersionContentLanguage')
            ->leftJoin('fileVersion.publishLanguages', 'fileVersionPublishLanguage')
            ->leftJoin('media.creator', 'creator')
            ->leftJoin('creator.contact', 'creatorContact')
            ->leftJoin('media.changer', 'changer')
            ->leftJoin('changer.contact', 'changerContact')
            ->addSelect('type')
            ->addSelect('collection')
            ->addSelect('file')
            ->addSelect('tag')
            ->addSelect('fileVersion')
            ->addSelect('fileVersionMeta')
            ->addSelect('fileVersionDefaultMeta')
            ->addSelect('fileVersionContentLanguage')
            ->addSelect('fileVersionPublishLanguage')
            ->addSelect('creator')
            ->addSelect('changer')
            ->addSelect('creatorContact')
            ->addSelect('changerContact');

        if (null !== $ids) {
            $queryBuilder->andWhere('media.id IN (:mediaIds)');
            $queryBuilder->setParameter('mediaIds', $ids);
        }

        $queryBuilder->addOrderBy($orderBy, $orderSort);

        if (null !== $permission) {
            if ($this->accessControlQueryEnhancer) {
                $this->accessControlQueryEnhancer->enhance(
                    $queryBuilder,
                    $user,
                    $permission,
                    Collection::class,
                    'collection'
                );
            } else {
                $this->addAccessControl(
                    $queryBuilder,
                    $user,
                    $permission,
                    Collection::class,
                    'collection'
                );
            }
        }

        /** @var MediaInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    public function findMediaDisplayInfo($ids, $locale)
    {
        $queryBuilder = $this->createQueryBuilder('media')
            ->leftJoin('media.files', 'file')
            ->leftJoin('file.fileVersions', 'fileVersion', Join::WITH, 'fileVersion.version = file.version')
            ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
            ->leftJoin('fileVersion.meta', 'fileVersionMeta', Join::WITH, 'fileVersionMeta.locale = :locale')
            ->select('media.id')
            ->addSelect('fileVersion.version')
            ->addSelect('fileVersion.name')
            ->addSelect('fileVersionMeta.title')
            ->addSelect('fileVersionDefaultMeta.title as defaultTitle')
            ->where('media.id IN (:mediaIds)');

        $queryBuilder->setParameter('locale', $locale);
        $queryBuilder->setParameter('mediaIds', $ids);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function count(array $filter = []): int
    {
        list($collection, $systemCollections, $types, $search) = $this->extractFilterVars($filter);

        $query = $this->getIdsQuery(
            $collection,
            $systemCollections,
            $types,
            $search,
            null,
            null,
            null,
            null,
            'COUNT(media)'
        );
        $result = $query->getSingleResult()[1];

        /** @var int<0, max> */
        return \intval($result);
    }

    /**
     * Extracts filter vars.
     *
     * @return array
     */
    private function extractFilterVars(array $filter)
    {
        $collection = \array_key_exists('collection', $filter) ? $filter['collection'] : null;
        $systemCollections = \array_key_exists('systemCollections', $filter) ? $filter['systemCollections'] : true;
        $types = \array_key_exists('types', $filter) ? $filter['types'] : null;
        $search = \array_key_exists('search', $filter) ? $filter['search'] : null;
        $orderBy = \array_key_exists('orderBy', $filter) ? $filter['orderBy'] : null;
        $orderSort = \array_key_exists('orderSort', $filter) ? $filter['orderSort'] : null;
        $ids = \array_key_exists('ids', $filter) ? $filter['ids'] : null;

        return [$collection, $systemCollections, $types, $search, $orderBy, $orderSort, $ids];
    }

    public function findMediaWithFilenameInCollectionWithId($filename, $collectionId)
    {
        $queryBuilder = $this->createQueryBuilder('media')
            ->innerJoin('media.files', 'files')
            ->innerJoin('files.fileVersions', 'versions', 'WITH', 'versions.version = files.version')
            ->join('media.collection', 'collection')
            ->where('collection.id = :collectionId')
            ->andWhere('versions.name = :filename')
            ->orderBy('versions.created')
            ->setMaxResults(1)
            ->setParameter('filename', $filename)
            ->setParameter('collectionId', $collectionId);

        /** @var MediaInterface[] $result */
        $result = $queryBuilder->getQuery()->getResult();

        if (\count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    public function findMediaByCollectionId($collectionId, $limit, $offset)
    {
        $queryBuilder = $this->createQueryBuilder('media')
            ->select('count(media.id) as counter')
            ->join('media.collection', 'collection')
            ->where('collection.id = :collectionId')
            ->setParameter('collectionId', $collectionId);

        /** @var int $count */
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        $queryBuilder = $this->createQueryBuilder('media')
            ->innerJoin('media.files', 'files')
            ->innerJoin('files.fileVersions', 'versions', 'WITH', 'versions.version = files.version')
            ->join('media.collection', 'collection')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->where('collection.id = :collectionId')
            ->setParameter('collectionId', $collectionId);

        $query = $queryBuilder->getQuery();
        /** @var Paginator<MediaInterface> $paginator */
        $paginator = new Paginator($query);

        return ['media' => $paginator, 'count' => $count];
    }

    /**
     * create a query for ids with given filter.
     *
     * @param string $collection
     * @param bool $systemCollections
     * @param array $types
     * @param string $search
     * @param string $orderBy
     * @param string $orderSort
     * @param int $limit
     * @param int $offset
     * @param string $select
     *
     * @return Query
     */
    private function getIdsQuery(
        $collection = null,
        $systemCollections = true,
        $types = null,
        $search = null,
        $orderBy = null,
        $orderSort = null,
        $limit = null,
        $offset = null,
        $select = 'media.id'
    ) {
        $queryBuilder = $this->createQueryBuilder('media')->select($select);

        $queryBuilder->innerJoin('media.collection', 'collection');

        if (!empty($collection)) {
            $queryBuilder->andWhere('collection.id = :collection');
            $queryBuilder->setParameter('collection', $collection);
        }

        if (!$systemCollections) {
            $queryBuilder->leftJoin('collection.type', 'collectionType');
            $queryBuilder->andWhere(
                \sprintf('collectionType.key != \'%s\'', SystemCollectionManagerInterface::COLLECTION_TYPE)
            );
        }

        if (!empty($types)) {
            $queryBuilder->innerJoin('media.type', 'type');
            $queryBuilder->andWhere('type.name IN (:types)');
            $queryBuilder->setParameter('types', $types);
        }

        if (!empty($search)) {
            $queryBuilder
                ->innerJoin('media.files', 'file')
                ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
                ->leftJoin('fileVersion.meta', 'fileVersionMeta');

            $queryBuilder->andWhere('fileVersionMeta.title LIKE :search');
            $queryBuilder->setParameter('search', '%' . $search . '%');
        }

        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if (!empty($orderBy)) {
            $queryBuilder->addOrderBy($orderBy, $orderSort);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * returns ids with given filters.
     *
     * @param string $collection
     * @param bool $systemCollections
     * @param array $types
     * @param string $search
     * @param string $orderBy
     * @param string $orderSort
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    private function getIds(
        $collection = null,
        $systemCollections = true,
        $types = null,
        $search = null,
        $orderBy = null,
        $orderSort = null,
        $limit = null,
        $offset = null
    ) {
        $subQuery = $this->getIdsQuery(
            $collection,
            $systemCollections,
            $types,
            $search,
            $orderBy,
            $orderSort,
            $limit,
            $offset
        );

        return $subQuery->getScalarResult();
    }

    /**
     * @return array<array{id: int, resourceKey: string, depth: int}>
     */
    public function findMediaResourcesByCollection(int $collectionId, bool $includeDescendantCollections = true): array
    {
        $qb = $this->createQueryBuilder('media')
            ->select('media.id AS id')
            ->addSelect('\'' . MediaInterface::RESOURCE_KEY . '\' AS resourceKey')
            ->addSelect('collection.depth + 1 AS depth')
            ->distinct()
            ->innerJoin('media.collection', 'collection');

        if (!$includeDescendantCollections) {
            $qb->where('collection.id = :collectionId');
        } else {
            $qb
                ->innerJoin(
                    CollectionInterface::class,
                    'ancestorCollection',
                    Join::WITH,
                    'collection.lft >= ancestorCollection.lft AND collection.rgt <= ancestorCollection.rgt'
                )
                ->where('ancestorCollection.id = :collectionId');
        }

        return $qb
            ->orderBy('media.id', 'ASC')
            ->setParameter('collectionId', $collectionId)
            ->getQuery()
            ->getArrayResult();
    }

    public function setAccessControlQueryEnhancer(AccessControlQueryEnhancer $accessControlQueryEnhancer)
    {
        $this->accessControlQueryEnhancer = $accessControlQueryEnhancer;
    }
}
