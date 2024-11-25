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

namespace Sulu\Content\Infrastructure\Sulu\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;

/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 */
class ContentSitemapProvider implements SitemapProviderInterface
{
    public const ROUTE_ALIAS = 'route';
    public const CONTENT_RICH_ENTITY_ALIAS = ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY;
    public const LOCALIZED_DIMENSION_CONTENT_ALIAS = 'localizedDimensionContent';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var string
     */
    protected $kernelEnvironment;

    /**
     * @var class-string<T>
     */
    protected $contentRichEntityClass;

    /**
     * @var class-string<RouteInterface>
     */
    protected $routeClass;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var int
     */
    protected $pageSize;

    /**
     * @param string $kernelEnvironment Inject parameter "kernel.environment" here
     * @param class-string<T> $contentRichEntityClass Classname that is used in the route table
     * @param class-string<RouteInterface> $routeClass
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        WebspaceManagerInterface $webspaceManager,
        string $kernelEnvironment,
        string $contentRichEntityClass,
        string $routeClass,
        string $alias
    ) {
        $this->entityManager = $entityManager;
        $this->webspaceManager = $webspaceManager;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->contentRichEntityClass = $contentRichEntityClass;
        $this->routeClass = $routeClass;
        $this->alias = $alias;
        $this->pageSize = self::PAGE_SIZE;
    }

    public function build($page, $scheme, $host): array
    {
        $limit = $this->pageSize;
        $offset = (int) (($page - 1) * $limit);

        $portalInformations = $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains(
            $host, $this->kernelEnvironment
        );

        /** @var PortalInformation|null $portalInformation */
        $portalInformation = \array_shift($portalInformations);

        if (!$portalInformation) {
            // TODO FIXME add testcase for this
            return []; // @codeCoverageIgnore
        }

        $webspace = $portalInformation->getWebspace();
        $defaultLocale = $webspace->getDefaultLocalization()->getLocale(Localization::DASH);

        $routes = $this->getRoutes($limit, $offset);
        $groupedRoutes = $this->groupRoutesByEntityId($routes);

        $result = [];

        foreach ($groupedRoutes as $entityId => $entityRoutes) {
            $mainRoute = null;

            if (\array_key_exists($defaultLocale, $entityRoutes)) {
                $mainRoute = $entityRoutes[$defaultLocale];
                unset($entityRoutes[$defaultLocale]);
            } else {
                $mainRoute = \array_shift($entityRoutes);
            }

            $sitemapUrl = $this->generateSitemapUrl(
                $mainRoute,
                $entityRoutes,
                $webspace->getKey(),
                $host,
                $scheme
            );

            if (null === $sitemapUrl) {
                // TODO FIXME add testcase for this
                continue; // @codeCoverageIgnore
            }

            $result[] = $sitemapUrl;
        }

        return $result;
    }

    public function createSitemap($scheme, $host): Sitemap
    {
        return new Sitemap(
            $this->getAlias(),
            $this->getMaxPage($scheme, $host)
        );
    }

    public function getMaxPage($scheme, $host): int
    {
        $queryBuilder = $this->createRoutesQueryBuilder();
        try {
            $amount = (int) $queryBuilder
                ->select('COUNT(' . self::ROUTE_ALIAS . ')')
                ->getQuery()
                ->getSingleScalarResult();

            return (int) \ceil($amount / $this->pageSize);
        } catch (NoResultException|NonUniqueResultException $e) { // @codeCoverageIgnore
            // TODO FIXME add testcase for this
            return 0; // @codeCoverageIgnore
        }
    }

    /**
     * @param RouteInterface[] $routes
     *
     * @return array<string, non-empty-array<string, RouteInterface>>
     */
    protected function groupRoutesByEntityId(array $routes): array
    {
        $result = [];

        foreach ($routes as $route) {
            $entityId = $route->getEntityId();

            if (!\array_key_exists($entityId, $result)) {
                $result[$entityId] = [];
            }

            $result[$entityId][$route->getLocale()] = $route;
        }

        return $result;
    }

    /**
     * @return RouteInterface[]
     */
    protected function getRoutes(int $limit, int $offset): array
    {
        $queryBuilder = $this->createRoutesQueryBuilder();

        $queryBuilder
            ->select(self::ROUTE_ALIAS)
            ->distinct()
            ->orderBy(self::ROUTE_ALIAS . '.entityId', 'asc')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var RouteInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    protected function createRoutesQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        return $queryBuilder
            ->from($this->contentRichEntityClass, self::CONTENT_RICH_ENTITY_ALIAS)
            ->innerJoin(self::CONTENT_RICH_ENTITY_ALIAS . '.dimensionContents', self::LOCALIZED_DIMENSION_CONTENT_ALIAS)
            ->innerJoin($this->routeClass, self::ROUTE_ALIAS, Join::WITH, self::ROUTE_ALIAS . '.entityId = ' . self::CONTENT_RICH_ENTITY_ALIAS . '.' . $this->getEntityIdField() . ' AND ' . self::ROUTE_ALIAS . '.locale = ' . self::LOCALIZED_DIMENSION_CONTENT_ALIAS . '.locale')
            ->where(self::LOCALIZED_DIMENSION_CONTENT_ALIAS . '.stage = :stage')
            ->andWhere(self::ROUTE_ALIAS . '.entityClass = :entityClass')
            ->andWhere(self::ROUTE_ALIAS . '.history = :history')
            ->setParameters([
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'entityClass' => $this->contentRichEntityClass,
                'history' => false,
            ]);
    }

    /**
     * @param RouteInterface[] $alternateRoutes
     */
    protected function generateSitemapUrl(
        RouteInterface $route,
        array $alternateRoutes,
        string $webspaceKey,
        string $host,
        string $scheme
    ): ?SitemapUrl {
        $url = $this->generateUrl($route, $webspaceKey, $host, $scheme);

        if (!$url) {
            // TODO FIXME add testcase for this
            return null; // @codeCoverageIgnore
        }

        $sitemapUrl = new SitemapUrl(
            $url,
            $route->getLocale(),
            $route->getLocale()
        );

        foreach ($alternateRoutes as $alternateRoute) {
            $alternateUrl = $this->generateUrl($alternateRoute, $webspaceKey, $host, $scheme);

            if (!$alternateUrl) {
                // TODO FIXME add testcase for this
                continue; // @codeCoverageIgnore
            }

            $alternateLink = new SitemapAlternateLink($alternateUrl, $alternateRoute->getLocale());

            $sitemapUrl->addAlternateLink($alternateLink);
        }

        return $sitemapUrl;
    }

    protected function generateUrl(
        RouteInterface $route,
        string $webspaceKey,
        string $host,
        string $scheme
    ): ?string {
        $url = $this->webspaceManager->findUrlByResourceLocator(
            $route->getPath(),
            $this->kernelEnvironment,
            $route->getLocale(),
            $webspaceKey,
            $host,
            $scheme
        );

        return $url ?: null;
    }

    protected function getEntityIdField(): string
    {
        return 'id';
    }

    public function getAlias()
    {
        return $this->alias;
    }
}
