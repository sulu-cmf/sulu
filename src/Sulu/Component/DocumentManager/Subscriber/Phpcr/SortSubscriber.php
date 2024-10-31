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

use Sulu\Component\DocumentManager\Event\SortEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SortSubscriber implements EventSubscriberInterface
{
    public function __construct(private NodeHelperInterface $nodeHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::SORT => ['handleSort', 500],
        ];
    }

    /**
     * Handle the reorder operation.
     *
     * @throws DocumentManagerException
     */
    public function handleSort(SortEvent $event): void
    {
        $this->nodeHelper->sort($event->getNode(), $event->getLocale());
    }
}
