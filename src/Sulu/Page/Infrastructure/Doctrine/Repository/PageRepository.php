<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Exception\PageNotFoundException;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Webmozart\Assert\Assert;

class PageRepository implements PageRepositoryInterface
{
    private const SELECTS = [
        // Contexts
        self::GROUP_SELECT_CONTEXT_ADMIN => [
            self::SELECT_PAGE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
            ],
        ],
        self::GROUP_SELECT_CONTEXT_WEBSITE => [
            self::SELECT_PAGE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
            ],
        ],
    ];

    protected ObjectRepository $entityRepository;

    protected string $pageClassName;

    protected ObjectRepository $entityDimensionContentRepository;

    protected string $pageDimensionContentClassName;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected DimensionContentQueryEnhancer $dimensionContentQueryEnhancer
    ) {

        $this->entityRepository = $this->entityManager->getRepository(PageInterface::class);
        $this->pageClassName = $this->entityRepository->getClassName();

        $this->entityDimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);
        $this->pageDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function createNew(?string $uuid = null): PageInterface
    {
        $className = $this->pageClassName;

        return new $className($uuid);
    }

    public function getOneBy(string $uuid, array $selects = []): PageInterface
    {
        $queryBuilder = $this->createQueryBuilder(['uuid' => $uuid], [], $selects);

        try {
            /** @var PageInterface $page */
            $page = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exc) {
            throw new PageNotFoundException(['uuid' => $uuid], 0, $exc);
        }

        return $page;
    }

    public function getOneWithContentBy(string $uuid, array $dimensionAttributes, array $selects = []): PageInterface
    {
        $queryBuilder = $this->createQueryBuilder(\array_merge(['uuid' => $uuid], $dimensionAttributes), [], $selects);

        try {
            /** @var PageInterface $page */
            $page = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exc) {
            throw new PageNotFoundException(['uuid' => $uuid], 0, $exc);
        }

        return $page;
    }

    public function add(PageInterface $Page): void
    {
        $this->entityManager->persist($Page);
    }

    public function remove(PageInterface $Page): void
    {
        $this->entityManager->remove($Page);
    }

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     loadGhost?: bool,
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     uuid?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     page_admin?: bool,
     *     page_website?: bool,
     *     with-page-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     */
    private function createQueryBuilder(array $filters, array $sortBy = [], array $selects = []): QueryBuilder
    {
        foreach ($selects as $selectGroup => $value) {
            if (!$value) {
                continue;
            }

            if (isset(self::SELECTS[$selectGroup])) {
                $selects = \array_replace_recursive($selects, self::SELECTS[$selectGroup]);
            }
        }

        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid);
            $queryBuilder->andWhere('page.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $uuids = $filters['uuids'] ?? null;
        if (null !== $uuids) {
            Assert::isArray($uuids);
            $queryBuilder->andWhere('page.uuid IN(:uuids)')
                ->setParameter('uuids', $uuids);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            Assert::integer($limit);
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if (null !== $page) {
            Assert::notNull($limit);
            Assert::integer($page);
            $offset = (int) ($limit * ($page - 1));
            $queryBuilder->setFirstResult($offset);
        }

        if (\array_key_exists('locale', $filters) // should also work with locale = null
            && \array_key_exists('stage', $filters)) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'page',
                $this->pageDimensionContentClassName,
                $filters,
                $sortBy
            );
        }

        if ([] !== $sortBy) {
            foreach ($sortBy as $field => $order) {
                if ('uuid' === $field) {
                    $queryBuilder->addOrderBy('page.uuid', $order);
                } elseif ('created' === $field) {
                    $queryBuilder->addOrderBy('page.created', $order);
                }
            }
        }

        // selects
        if ($selects[self::SELECT_PAGE_CONTENT] ?? null) {
            /** @var array<string, bool> $contentSelects */
            $contentSelects = $selects[self::SELECT_PAGE_CONTENT];

            $queryBuilder->leftJoin(
                'page.dimensionContents',
                'dimensionContent'
            );

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                $this->pageDimensionContentClassName,
                $filters,
                $contentSelects
            );
        }

        return $queryBuilder;
    }
}
