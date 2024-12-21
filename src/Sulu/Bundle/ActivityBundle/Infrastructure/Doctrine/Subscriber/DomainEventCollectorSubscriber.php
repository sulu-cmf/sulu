<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Subscriber;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;

class DomainEventCollectorSubscriber
{
    public function __construct(
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->domainEventCollector->clear();
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->domainEventCollector->dispatch();
    }
}
