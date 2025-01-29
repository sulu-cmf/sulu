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
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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
     * @var array<int, array{oldValue: string, newValue: string, locale: string, site: string|null}>
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
                ->select('parent.id AS parent_id')
                ->innerJoin('parent', $routesTableName, 'child', 'child.parent_id = parent.id')
                ->andWhere(\is_string($site) ? 'parent.site = :site' : 'parent.site IS NULL')
                ->andWhere('parent.locale = :locale')
                ->andWhere('(parent.slug = :newSlug OR parent.slug LIKE :oldSlugSlash)') // direct child is using newSlug already updated as we are in PostFlush, grand child use oldSlugWithSlash as not yet updated
                ->setParameter('newSlug', $newSlug, ParameterType::STRING)
                ->setParameter('oldSlugSlash', $oldSlug . '/%', ParameterType::STRING)
                ->setParameter('locale', $locale, ParameterType::STRING);

            if (\is_string($site)) {
                $selectQueryBuilder->setParameter('site', $site, ParameterType::STRING);
            }

            $parentIds = \array_map(fn ($row) => $row[0], $selectQueryBuilder->executeQuery()->fetchAllNumeric());
            $parentIds = \array_filter($parentIds);

            if (0 === \count($parentIds)) {
                continue;
            }

            $parentIds = \array_unique($parentIds); // DISTINCT and GROUP BY a lot slower as make it unique in PHP itself

            // TODO create history for current ids

            $newSlugCast = '';
            if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                $newSlugCast = '::text'; // concat seems not directly supported by dbal and parameter $1 (newSlug) is not cast to text correctly. So manually cast it here.
            }

            // update child and grand routes
            $updateQueryBuilder = $connection->createQueryBuilder()
                ->update($routesTableName, 'r')
                ->set('slug', 'CONCAT(:newSlug' . $newSlugCast . ', SUBSTRING(slug, ' . (\strlen($oldSlug) + 1) . '))')
                ->setParameter('newSlug', $newSlug, ParameterType::STRING)
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
