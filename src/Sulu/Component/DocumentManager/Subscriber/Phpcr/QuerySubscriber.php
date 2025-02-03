<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles creation of query and query builder objects.
 */
class QuerySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SessionInterface $session,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::QUERY_CREATE => ['handleCreate', 500],
            Events::QUERY_CREATE_BUILDER => ['handleCreateBuilder', 500],
            Events::QUERY_EXECUTE => ['handleQueryExecute', 500],
        ];
    }

    /**
     * Create a new Sulu Query object.
     */
    public function handleCreate(QueryCreateEvent $event)
    {
        $innerQuery = $event->getInnerQuery();

        if (\is_string($innerQuery)) {
            $phpcrQuery = $this->getQueryManager()->createQuery($innerQuery, QueryInterface::JCR_SQL2);
        } elseif ($innerQuery instanceof QueryInterface) {
            $phpcrQuery = $innerQuery;
        } else {
            throw new \InvalidArgumentException(\sprintf(
                'Expected inner query to be either a string or a PHPCR query object, got: "%s"',
                \is_object($innerQuery) ? \get_class($innerQuery) : \gettype($innerQuery)
            ));
        }

        $event->setQuery(
            new Query(
                $phpcrQuery,
                $this->eventDispatcher,
                $event->getLocale(),
                $event->getOptions(),
                $event->getPrimarySelector()
            )
        );
    }

    /**
     * Handle query execution.
     */
    public function handleQueryExecute(QueryExecuteEvent $event)
    {
        $query = $event->getQuery();
        $locale = $query->getLocale();
        $phpcrResult = $query->getPhpcrQuery()->execute();

        $event->setResult(
            new QueryResultCollection($phpcrResult, $this->eventDispatcher, $locale, $event->getOptions())
        );
    }

    /**
     * @return QueryManagerInterface
     */
    private function getQueryManager()
    {
        return $this->session->getWorkspace()->getQueryManager();
    }
}
