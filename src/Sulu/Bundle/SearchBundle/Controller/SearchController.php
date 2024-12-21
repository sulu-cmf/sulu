<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\SearchBundle\Search\Configuration\IndexConfiguration;
use Sulu\Bundle\SearchBundle\Search\Configuration\IndexConfigurationProviderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    public function __construct(
        private SearchManagerInterface $searchManager,
        private ProviderInterface $metadataProvider,
        private SecurityCheckerInterface $securityChecker,
        private ViewHandlerInterface $viewHandler,
        private ListRestHelperInterface $listRestHelper,
        private IndexConfigurationProviderInterface $indexConfigurationProvider,
    ) {
    }

    /**
     * Perform a search and return a JSON response.
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $queryString = $request->query->get('q');
        $index = $request->query->get('index', null);
        $locale = $request->query->get('locale', null);

        $page = $this->listRestHelper->getPage();
        $limit = $this->listRestHelper->getLimit();
        $offset = $this->listRestHelper->getOffset() ?: 0;

        $indexNames = $this->searchManager->getIndexNames();

        $indexes = \array_filter(
            $index
                ? [$index]
                : \array_map(function(IndexConfiguration $index) {
                    return $index->getIndexName();
                }, $this->getAllowedIndexes()),
            function(string $indexName) use ($indexNames) {
                return false !== \array_search($indexName, $indexNames);
            }
        );

        $query = $this->searchManager->createSearch($queryString);

        if ($locale) {
            $query->locale($locale);
        }

        $query->indexes($indexes);
        $query->setLimit((int) $limit);
        $query->setOffset($offset);

        $result = $query->execute();
        $total = $result->getTotal();

        $representation = new PaginatedRepresentation(
            $result,
            'result',
            (int) $page,
            (int) $limit,
            $total
        );

        $view = View::create($representation);
        $context = new Context();
        $context->enableMaxDepth();
        $view->setContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return a JSON encoded scalar array of index names.
     *
     * @return Response
     */
    public function indexesAction()
    {
        return $this->viewHandler->handle(
            View::create(
                [
                    '_embedded' => [
                        'search_indexes' => \array_values($this->getAllowedIndexes()),
                    ],
                ]
            )
        );
    }

    /**
     * @return IndexConfiguration[]
     */
    private function getAllowedIndexes()
    {
        return \array_filter(
            $this->indexConfigurationProvider->getIndexConfigurations(),
            function(IndexConfiguration $indexConfiguration) {
                $securityContext = $indexConfiguration->getSecurityContext();
                $contexts = $indexConfiguration->getContexts();

                return $this->securityChecker->hasPermission($securityContext, PermissionTypes::VIEW)
                    && empty($contexts) || false !== \array_search('admin', $contexts);
            }
        );
    }
}
