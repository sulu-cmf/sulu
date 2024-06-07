<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Sitemap;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\DocumentFactoryInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Integrates articles into sitemap.
 */
class ArticleSitemapProvider implements SitemapProviderInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var DocumentFactoryInterface
     */
    private $documentFactory;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(
        Manager $manager,
        DocumentFactoryInterface $documentFactory,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->manager = $manager;
        $this->documentFactory = $documentFactory;
        $this->webspaceManager = $webspaceManager;
    }

    public function build($page, $scheme, $host)
    {
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));

        $webspaceKeys = $this->getWebspaceKeysByHost($host);

        $result = [];
        $from = 0;
        $size = 1000;

        do {
            $bulk = $this->getBulk($repository, $webspaceKeys, $from, $size);
            /** @var SitemapUrl[] $sitemapUrlListByUuid */
            $sitemapUrlListByUuid = [];

            /** @var ArticleViewDocumentInterface $item */
            foreach ($bulk as $item) {
                // Get all webspace keys which are for the current document and current selected webspaces
                $itemWebspaceKeys = \array_intersect(
                    \array_merge([$item->getMainWebspace()], $item->getAdditionalWebspaces()),
                    $webspaceKeys
                );

                foreach ($itemWebspaceKeys as $itemWebspaceKey) {
                    $url = $this->buildUrl($item, $scheme, $host, $itemWebspaceKey);

                    $result[] = $url;

                    $alternativeUrlsKey = $itemWebspaceKey . '__' . $item->getUuid();
                    if (!isset($sitemapUrlListByUuid[$alternativeUrlsKey])) {
                        $sitemapUrlListByUuid[$alternativeUrlsKey] = [];
                    }

                    $sitemapUrlListByUuid[$alternativeUrlsKey] = $this->setAlternatives(
                        $sitemapUrlListByUuid[$alternativeUrlsKey],
                        $url
                    );
                }
            }

            $from += $size;
        } while ($bulk->count() > $from && $from < static::PAGE_SIZE);

        return $result;
    }

    protected function buildUrl(
        ArticleViewDocumentInterface $articleView,
        string $scheme,
        string $host,
        string $webspaceKey
    ): SitemapUrl {
        return new SitemapUrl(
            $this->findUrl($articleView, $scheme, $host, $webspaceKey),
            $articleView->getLocale(),
            null,
            $articleView->getLastModified() ?? $articleView->getChanged()
        );
    }

    private function findUrl(
        ArticleViewDocumentInterface $articleView,
        string $scheme,
        string $host,
        string $webspaceKey
    ): string {
        return $this->webspaceManager->findUrlByResourceLocator(
            $articleView->getRoutePath(),
            null,
            $articleView->getLocale(),
            $webspaceKey,
            $host,
            $scheme
        );
    }

    /**
     * Set alternatives to sitemap url.
     *
     * @param SitemapUrl[] $sitemapUrlList
     *
     * @return SitemapUrl[]
     */
    private function setAlternatives(array $sitemapUrlList, SitemapUrl $sitemapUrl): array
    {
        foreach ($sitemapUrlList as $sitemapUrlFromList) {
            // Add current as alternative to exist.
            $sitemapUrlFromList->addAlternateLink(
                new SitemapAlternateLink($sitemapUrl->getLoc(), $sitemapUrl->getLocale())
            );

            // Add others as alternative to current.
            $sitemapUrl->addAlternateLink(
                new SitemapAlternateLink($sitemapUrlFromList->getLoc(), $sitemapUrlFromList->getLocale())
            );
        }

        $sitemapUrlList[] = $sitemapUrl;

        return $sitemapUrlList;
    }

    private function getBulk(Repository $repository, array $webspaceKeys, int $from, int $size): DocumentIterator
    {
        $search = $repository->createSearch()
            ->addQuery(new TermQuery('seo.hide_in_sitemap', 'false'))
            ->setFrom($from)
            ->setSize($size);

        $webspaceQuery = new BoolQuery();
        foreach ($webspaceKeys as $webspaceKey) {
            $webspaceQuery->add(new TermQuery('main_webspace', $webspaceKey), BoolQuery::SHOULD);
            $webspaceQuery->add(new TermQuery('additional_webspaces', $webspaceKey), BoolQuery::SHOULD);
        }

        $search->addQuery($webspaceQuery);
        if (\method_exists($search, 'setTrackTotalHits')) {
            $search->setTrackTotalHits(true);
        }

        return $repository->findDocuments($search);
    }

    public function createSitemap($scheme, $host)
    {
        return new Sitemap($this->getAlias(), $this->getMaxPage($scheme, $host));
    }

    public function getMaxPage($schema, $host)
    {
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch()
            ->addQuery(new TermQuery('seo.hide_in_sitemap', 'false'));

        $webspaceKeys = $this->getWebspaceKeysByHost($host);

        $webspaceQuery = new BoolQuery();
        foreach ($webspaceKeys as $webspaceKey) {
            $webspaceQuery->add(new TermQuery('main_webspace', $webspaceKey), BoolQuery::SHOULD);
            $webspaceQuery->add(new TermQuery('additional_webspaces', $webspaceKey), BoolQuery::SHOULD);
        }

        $search->addQuery($webspaceQuery);

        return \ceil($repository->count($search) / static::PAGE_SIZE);
    }

    /**
     * @return string[]
     */
    private function getWebspaceKeysByHost(string $host): array
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains($host);

        $webspaceKeys = [];
        foreach ($portalInformations as $portalInformation) {
            $webspaceKeys[] = $portalInformation->getWebspaceKey();
        }

        return $webspaceKeys;
    }

    public function getAlias()
    {
        return 'articles';
    }
}
