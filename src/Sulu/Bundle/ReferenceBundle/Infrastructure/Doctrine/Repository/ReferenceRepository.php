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

namespace Sulu\Bundle\ReferenceBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\ReferenceBundle\Domain\Exception\ReferenceNotFoundException;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

/**
 * @phpstan-import-type ReferenceFilters from ReferenceRepositoryInterface
 * @phpstan-import-type ReferenceSortBys from ReferenceRepositoryInterface
 * @phpstan-import-type ReferenceFields from ReferenceRepositoryInterface
 */
final class ReferenceRepository implements ReferenceRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    /**
     * @var EntityRepository<ReferenceInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var class-string<ReferenceInterface>
     */
    private string $className;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->entityRepository = $this->entityManager->getRepository(ReferenceInterface::class);
        $this->className = $this->entityRepository->getClassName();
    }

    public function create(
        string $resourceKey,
        string $resourceId,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        string $referenceContext,
        string $referenceProperty,
        array $referenceRouterAttributes = [],
    ): ReferenceInterface {
        /** @var ReferenceInterface $reference */
        $reference = new $this->className();

        $reference
            ->setResourceKey($resourceKey)
            ->setResourceId($resourceId)
            ->setReferenceLocale($referenceLocale)
            ->setReferenceResourceKey($referenceResourceKey)
            ->setReferenceResourceId($referenceResourceId)
            ->setReferenceTitle($referenceTitle)
            ->setReferenceContext($referenceContext)
            ->setReferenceRouterAttributes($referenceRouterAttributes)
            ->setReferenceProperty($referenceProperty);

        return $reference;
    }

    public function add(ReferenceInterface $reference): void
    {
        $this->entityManager->persist($reference);
    }

    public function remove(ReferenceInterface $reference): void
    {
        $this->entityManager->remove($reference);
    }

    public function removeBy(array $filters): void
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder
            ->delete(ReferenceInterface::class, 'reference');

        $queryBuilder->getQuery()->execute();
    }

    public function getOneBy(array $filters): ReferenceInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        try {
            /** @var ReferenceInterface */
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            throw new ReferenceNotFoundException($filters, $exception);
        }
    }

    public function findOneBy(array $filters): ?ReferenceInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        try {
            /** @var ReferenceInterface */
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            return null;
        }
    }

    public function findFlatBy(array $filters = [], array $sortBys = [], array $fields = [], bool $distinct = false): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBys, $distinct);
        $this->addSelectFields($queryBuilder, $fields);

        /**
         * @var array{
         *     referenceTitle?: string,
         *     referenceResourceKey?: string,
         *     referenceResourceId?: string,
         *     referenceRouterAttributes?: array<string, string>,
         *     referenceContext?: string,
         *     referenceProperty?: string,
         * } $row
         */
        foreach ($queryBuilder->getQuery()->toIterable() as $row) {
            yield $row;
        }
    }

    public function count(array $filters = [], array $distinctFields = []): int
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        if (0 === \count($distinctFields)) {
            $queryBuilder->select('COUNT(reference)');

            /** @var int */
            return $queryBuilder->getQuery()->getSingleScalarResult();
        }

        $this->addSelectFields($queryBuilder, $distinctFields);
        $this->addGroupByFields($queryBuilder, $distinctFields);

        // currently counted in PHP else we would need to create a distinctField
        //     as COUNT(DISTINCT CONCAT(field1, field2, ...)) is would also be slow
        return \count($queryBuilder->getQuery()->getArrayResult());
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * @param ReferenceFilters $filters
     * @param ReferenceSortBys $sortBys
     */
    private function createQueryBuilder(array $filters = [], array $sortBys = [], bool $distinct = false): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('reference');

        $id = $filters['id'] ?? null;
        if (null !== $id) {
            $queryBuilder->andWhere('reference.id = :id')
                ->setParameter('id', $id);
        }

        $referenceResourceKey = $filters['referenceResourceKey'] ?? null;
        if (null !== $referenceResourceKey) {
            $queryBuilder->andWhere('reference.referenceResourceKey = :referenceResourceKey')
                ->setParameter('referenceResourceKey', $referenceResourceKey);
        }

        $resourceId = $filters['resourceId'] ?? null;
        if (null !== $resourceId) {
            $queryBuilder->andWhere('reference.resourceId = :resourceId')
                ->setParameter('resourceId', $resourceId);
        }

        $resourceKey = $filters['resourceKey'] ?? null;
        if (null !== $resourceKey) {
            $queryBuilder->andWhere('reference.resourceKey = :resourceKey')
                ->setParameter('resourceKey', $resourceKey);
        }

        $referenceResourceKey = $filters['referenceResourceKey'] ?? null;
        if (null !== $referenceResourceKey) {
            $queryBuilder->andWhere('reference.referenceResourceKey = :referenceResourceKey')
                ->setParameter('referenceResourceKey', $referenceResourceKey);
        }

        $referenceResourceId = $filters['referenceResourceId'] ?? null;
        if (null !== $referenceResourceId) {
            $queryBuilder->andWhere('reference.referenceResourceId = :referenceResourceId')
                ->setParameter('referenceResourceId', $referenceResourceId);
        }

        $referenceLocale = $filters['referenceLocale'] ?? null;
        if (null !== $referenceLocale) {
            $queryBuilder->andWhere('reference.referenceLocale = :referenceLocale')
                ->setParameter('referenceLocale', $referenceLocale);
        }

        $referenceContext = $filters['referenceContext'] ?? null;
        if (null !== $referenceContext) {
            $queryBuilder->andWhere('reference.referenceContext = :referenceContext')
                ->setParameter('referenceContext', $referenceContext);
        }

        $changedOlderThan = $filters['changedOlderThan'] ?? null;
        if (null !== $changedOlderThan) {
            $queryBuilder->andWhere('reference.changed < :changedOlderThan')
                ->setParameter('changedOlderThan', $changedOlderThan);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        $offset = $filters['offset'] ?? null;
        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        foreach ($sortBys as $field => $direction) {
            $queryBuilder->addOrderBy('reference.' . $field, $direction);
        }

        $queryBuilder->distinct($distinct);

        return $queryBuilder;
    }

    /**
     * @param ReferenceFields $fields
     */
    private function addSelectFields(QueryBuilder $queryBuilder, array $fields): void
    {
        $isFirst = true;
        foreach ($fields as $field) {
            if (true === $isFirst) {
                // doctrine automatically calls `select` so we need to overwrite it
                $queryBuilder->select('reference.' . $field);
                $isFirst = false;

                continue;
            }

            $queryBuilder->addSelect('reference.' . $field);
        }
    }

    /**
     * @param ReferenceFields $fields
     */
    private function addGroupByFields(QueryBuilder $queryBuilder, array $fields): void
    {
        foreach ($fields as $field) {
            $queryBuilder->addGroupBy('reference.' . $field);
        }
    }
}
