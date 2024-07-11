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

    /**
     * @var QueryResultCollection
     */
    private $result;

    public function __construct(private Query $query, array $options = [])
    {
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
