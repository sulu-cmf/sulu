<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Collection;

use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lazily hydrate query results.
 */
class QueryResultCollection extends AbstractLazyCollection
{
    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher;

    private \PHPCR\Query\QueryResultInterface $result;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $options;

    private bool $initialized = false;

    /**
     * @var null|string
     */
    private $primarySelector = null;

    /**
     * @param string $locale
     * @param array $options
     * @param null|string $primarySelector
     */
    public function __construct(
        QueryResultInterface $result,
        EventDispatcherInterface $eventDispatcher,
        $locale,
        $options = [],
        $primarySelector = null
    ) {
        $this->result = $result;
        $this->eventDispatcher = $eventDispatcher;
        $this->locale = $locale;
        $this->options = $options;
        $this->primarySelector = $primarySelector;
    }

    public function current()
    {
        $this->initialize();
        $row = $this->documents->current();
        $node = $row->getNode($this->primarySelector);

        $hydrateEvent = new HydrateEvent($node, $this->locale, $this->options);
        $this->eventDispatcher->dispatch($hydrateEvent, Events::HYDRATE);

        return $hydrateEvent->getDocument();
    }

    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $this->documents = $this->result->getRows();
        $this->initialized = true;
    }
}
