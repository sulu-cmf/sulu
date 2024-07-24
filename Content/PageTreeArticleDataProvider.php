<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\SmartContent\DatasourceItem;

/**
 * Introduces articles in smart-content and provides the page tree as data-source.
 */
class PageTreeArticleDataProvider extends ArticleDataProvider
{
    public function getConfiguration()
    {
        return $this->getConfigurationBuilder()
            ->enableDatasource('pages', 'pages', 'column_list')
            ->getConfiguration();
    }

    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        if (!$datasource) {
            return null;
        }

        /** @var string|null $locale */
        $locale = $options['locale'];
        $document = $this->documentManager->find($datasource, $locale);

        if (!$document instanceof BasePageDocument) {
            return null;
        }

        return new DatasourceItem($document->getUuid(), $document->getTitle(), $document->getResourceSegment());
    }

    protected function createSearch(Search $search, array $filters, string $locale): Search
    {
        $search = parent::createSearch($search, $filters, $locale);

        if (!\array_key_exists('dataSource', $filters) || !$filters['dataSource']) {
            return $search;
        }

        $document = $this->documentManager->find($filters['dataSource'], $locale);

        if (!$document instanceof BasePageDocument || !$document->getResourceSegment()) {
            return $search;
        }

        $includeSubFolders = $filters['includeSubFolders'] ?? null;

        if (true === $includeSubFolders) {
            $search->addQuery(new PrefixQuery('route_path.raw', \rtrim($document->getResourceSegment(), '/') . '/'));

            return $search;
        }

        $search->addQuery(new TermQuery('parent_page_uuid', $document->getUuid()));

        return $search;
    }
}
