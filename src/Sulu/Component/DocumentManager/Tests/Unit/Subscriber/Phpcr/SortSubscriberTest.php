<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Phpcr;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Event\SortEvent;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\SortSubscriber;

class SortSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeHelperInterface>
     */
    private ObjectProphecy $nodeHelper;

    private SortSubscriber $sortSubscriber;

    public function setUp(): void
    {
        $this->nodeHelper = $this->prophesize(NodeHelperInterface::class);
        $this->sortSubscriber = new SortSubscriber($this->nodeHelper->reveal());
    }

    public function testHandleReorder(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $event = $this->prophesize(SortEvent::class);

        $event->getNode()->willReturn($node->reveal())->shouldBeCalled();
        $event->getLocale()->willReturn('en');

        $this->nodeHelper->sort($node->reveal(), 'en')->shouldBeCalled();

        $this->sortSubscriber->handleSort($event->reveal());
    }
}
