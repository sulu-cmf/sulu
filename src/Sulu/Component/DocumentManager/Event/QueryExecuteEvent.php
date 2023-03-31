<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Query\Query;

class QueryExecuteEvent extends AbstractEvent
{
    use EventOptionsTrait;

    private \Sulu\Component\DocumentManager\Query\Query $query;

    private ?\Sulu\Component\DocumentManager\Collection\QueryResultCollection $result = null;

    public function __construct(Query $query, array $options = [])
    {
        $this->query = $query;
        $this->options = $options;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function setResult(QueryResultCollection $collection)
    {
        $this->result = $collection;
    }

    public function getResult()
    {
        return $this->result;
    }
}
