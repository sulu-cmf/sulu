<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Application\Dispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollector;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class DomainEventCollectorTest extends TestCase
{
    /**
     * @var ObjectProphecy<DomainEventDispatcherInterface>
     */
    private $domainEventDispatcher;

    public function setUp(): void
    {
        $this->domainEventDispatcher = $this->prophesize(DomainEventDispatcherInterface::class);
    }

    public function testCollectAndDispatch(): void
    {
        $collector = $this->createDomainEventCollector();

        $event1 = $this->prophesize(DomainEvent::class);
        $collector->collect($event1->reveal());

        $event2 = $this->prophesize(DomainEvent::class);
        $collector->collect($event2->reveal());

        $event3 = $this->prophesize(DomainEvent::class);
        $collector->collect($event3->reveal());

        $event1->getEventBatch()->willReturn(null);
        $event1->setEventBatch(Argument::type('string'))->shouldBeCalled();
        $this->domainEventDispatcher->dispatch($event1->reveal())->shouldBeCalled();

        $event2->getEventBatch()->willReturn(null);
        $event2->setEventBatch(Argument::type('string'))->shouldBeCalled();
        $this->domainEventDispatcher->dispatch($event2->reveal())->shouldBeCalled();

        $event3->getEventBatch()->willReturn('batch-1234');
        $event3->setEventBatch(Argument::cetera())->shouldNotBeCalled();
        $this->domainEventDispatcher->dispatch($event3->reveal())->shouldBeCalled();

        $collector->dispatch();
    }

    public function testCollectWithDispatchAfterClear(): void
    {
        $collector = $this->createDomainEventCollector();

        $event1 = $this->prophesize(DomainEvent::class);
        $collector->collect($event1->reveal());

        $event2 = $this->prophesize(DomainEvent::class);
        $collector->collect($event2->reveal());

        $this->domainEventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $collector->clear();

        $collector->dispatch();
    }

    public function testCollectWithoutDispatch(): void
    {
        $collector = $this->createDomainEventCollector();

        $event1 = $this->prophesize(DomainEvent::class);
        $collector->collect($event1->reveal());

        $event2 = $this->prophesize(DomainEvent::class);
        $collector->collect($event2->reveal());

        $this->domainEventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();
    }

    public function testDispatchWithoutCollect(): void
    {
        $collector = $this->createDomainEventCollector();

        $this->domainEventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $collector->dispatch();
    }

    private function createDomainEventCollector(): DomainEventCollector
    {
        return new DomainEventCollector(
            $this->domainEventDispatcher->reveal()
        );
    }
}
