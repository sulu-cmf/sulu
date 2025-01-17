<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Doctrine\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Sulu\Route\Domain\Model\Route;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal No BC promise are given for this class. Can be changed or removed at any time.
 */
class RouteChangedUpdater implements ResetInterface
{
    /**
     * @var array<int, array{oldValue: string, newValue: string, locale: string, site: string}>
     */
    private array $routeChanges = [];

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $route = $args->getObject();
        if (!$route instanceof Route) {
            return;
        }

        $oldSlug = $args->getOldValue('slug');
        $newSlug = $args->getNewValue('slug');

        if ($oldSlug === $newSlug) {
            return;
        }

        $this->routeChanges[$route->getId()] = [
            'oldValue' => $oldSlug,
            'newValue' => $newSlug,
            'locale' => $route->getLocale(),
            'site' => $route->getSite(),
        ];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (0 === \count($this->routeChanges)) {
            return;
        }

        $connection = $args->getObjectManager()->getConnection();

        $routesTableName = $args->getObjectManager()->getClassMetadata(Route::class)->getTableName();

        foreach ($this->routeChanges as $routeChange) {
            $oldSlug = $routeChange['oldValue'];
            $newSlug = $routeChange['newValue'];
            $locale = $routeChange['locale'];
            $site = $routeChange['site'];

            // select all child and grand routes of oldSlug
            $selectQueryBuilder = $connection->createQueryBuilder()
                ->from($routesTableName, 'parent')
                ->select('parent.id as parentId')
                ->innerJoin('parent', $routesTableName, 'child', 'child.parent_id = parent.id')
                ->andWhere('(parent.site = :site)')
                ->andWhere('parent.locale = :locale')
                ->andWhere('(parent.slug = :newSlug OR parent.slug LIKE :oldSlugSlash)') // direct child is using newSlug already updated as we are in PostFlush, grand child use oldSlugWithSlash as not yet updated
                ->setParameter('newSlug', $newSlug)
                ->setParameter('oldSlugSlash', $oldSlug . '/%')
                ->setParameter('locale', $locale)
                ->setParameter('site', $site);

            $parentIds = \array_map(fn ($row) => $row['parentId'] ?? null, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
            $parentIds = \array_filter($parentIds);

            if (0 === \count($parentIds)) {
                continue;
            }

            $parentIds = \array_unique($parentIds); // DISTINCT and GROUP BY a lot slower as make it unique in PHP itself

            // TODO create history for current ids

            // update child and grand routes
            $updateQueryBuilder = $connection->createQueryBuilder()->update($routesTableName, 'r')
                ->set('slug', 'CONCAT(:newSlug, SUBSTRING(r.slug, LENGTH(:oldSlug) + 1))')
                ->setParameter('newSlug', $newSlug)
                ->setParameter('oldSlug', $oldSlug)
                ->where('parent_id IN (:parentIds)')
                ->setParameter('parentIds', $parentIds, ArrayParameterType::INTEGER);

            $updateQueryBuilder->executeStatement();
        }
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->routeChanges = [];
    }
}
