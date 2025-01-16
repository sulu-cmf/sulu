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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\MediaBundle\Api\Media as MediaApi;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

/**
 * Implements find by filter for media data-provider.
 */
class MediaDataProviderRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait {
        findByFilters as parentFindByFilters;
    }

    /**
     * @param string $mediaEntityName
     * @param string $collectionEntityName
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaManagerInterface $mediaManager,
        private $mediaEntityName,
        private $collectionEntityName,
        ?AccessControlQueryEnhancer $accessControlQueryEnhancer = null
    ) {
        $this->accessControlQueryEnhancer = $accessControlQueryEnhancer;
    }

    public function findByFilters(
        $filters,
        $page,
        $pageSize,
        $limit,
        $locale,
        $options = [],
        ?UserInterface $user = null,
        $permission = null
    ) {
        if (!\array_key_exists('dataSource', $filters)
            || '' === $filters['dataSource']
            || (null !== $limit && $limit < 1)
        ) {
            return [];
        }

        if ('root' === $filters['dataSource']) {
            // if root collection is selected remove filter for data-source
            $filters['dataSource'] = null;
        }

        $entities = $this->parentFindByFilters(
            $filters,
            $page,
            $pageSize,
            $limit,
            $locale,
            $options,
            $user,
            Collection::class,
            'collection',
            $permission
        );

        return \array_map(
            function(Media $media) use ($locale) {
                return $this->mediaManager->addFormatsAndUrl(new MediaApi($media, $locale));
            },
            $entities
        );
    }

    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        $queryBuilder
            ->addSelect('type')
            ->addSelect('file')
            ->addSelect('fileVersion')
            ->addSelect('fileVersionMeta')
            ->addSelect('fileVersionDefaultMeta')
            ->addSelect('fileVersionContentLanguage')
            ->addSelect('fileVersionPublishLanguage')
            ->addSelect('creator')
            ->addSelect('changer')
            ->addSelect('creatorContact')
            ->addSelect('changerContact')
            ->leftJoin($alias . '.type', 'type')
            ->leftJoin($alias . '.files', 'file')
            ->leftJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
            ->leftJoin(
                'fileVersion.meta',
                'fileVersionMeta',
                Join::WITH,
                'fileVersionMeta.locale = :locale'
            )
            ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
            ->leftJoin('fileVersion.contentLanguages', 'fileVersionContentLanguage')
            ->leftJoin('fileVersion.publishLanguages', 'fileVersionPublishLanguage')
            ->leftJoin($alias . '.creator', 'creator')
            ->leftJoin('creator.contact', 'creatorContact')
            ->leftJoin($alias . '.changer', 'changer')
            ->leftJoin('changer.contact', 'changerContact')
            ->setParameter('locale', $locale);
    }

    protected function append(QueryBuilder $queryBuilder, $alias, $locale, $options = [])
    {
        $parameter = [];

        if (\array_key_exists('mimetype', $options)) {
            $queryBuilder
                ->andWhere('fileVersion.mimeType = :mimeType');

            $parameter['mimeType'] = $options['mimetype'];
        }
        if (\array_key_exists('type', $options)) {
            $queryBuilder
                ->innerJoin($alias . '.type', 'type')
                ->andWhere('type.name = :type');

            $parameter['type'] = $options['type'];
        }

        return $parameter;
    }

    protected function appendTagsRelation(QueryBuilder $queryBuilder, $alias)
    {
        $queryBuilder
            ->innerJoin($alias . '.files', 'file')
            ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version');

        return 'fileVersion.tags';
    }

    protected function appendCategoriesRelation(QueryBuilder $queryBuilder, $alias)
    {
        return 'fileVersion.categories';
    }

    protected function appendTargetGroupRelation(QueryBuilder $queryBuilder, $alias)
    {
        return 'fileVersion.targetGroups';
    }

    protected function appendDatasource($datasource, $includeSubFolders, QueryBuilder $queryBuilder, $alias)
    {
        if (!$includeSubFolders) {
            $queryBuilder->andWhere('collection.id = :collectionId');
        } else {
            $queryBuilder
                ->innerJoin(
                    $this->collectionEntityName,
                    'parentCollection',
                    Join::WITH,
                    'parentCollection.id = :collectionId'
                )
                ->where('collection.lft BETWEEN parentCollection.lft AND parentCollection.rgt');
        }

        return ['collectionId' => $datasource];
    }

    protected function appendSortByJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        $queryBuilder
            ->leftJoin(
                'fileVersion.meta',
                'fileVersionMeta',
                Join::WITH,
                'fileVersionMeta.locale = :locale'
            )
            ->setParameter('locale', $locale);
    }

    public function createQueryBuilder($alias, $indexBy = null): \Doctrine\ORM\QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->addSelect('collection')
            ->from($this->mediaEntityName, $alias, $indexBy)
            ->innerJoin($alias . '.collection', 'collection');
    }
}
