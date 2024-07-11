<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Controller;

use Massive\Bundle\SearchBundle\Search\SearchManager;
use Symfony\Component\HttpFoundation\Request;

class SearchController
{
    public function __construct(private SearchManager $searchManager)
    {
    }

    public function queryAction(Request $request)
    {
        $q = $request->get('query', '');

        if (\strlen($q) <= 3) {
            throw new \Exception('Length of query string must be greater than 3 (Zend Search)');
        }

        $hits = $this->searchManager->createSearch($q)->locale('de')->index('content');

        return $this->render('@Test/Search/query.html.twig', [
            'hits' => $hits,
        ]);
    }
}
