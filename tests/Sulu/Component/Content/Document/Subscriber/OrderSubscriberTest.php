<?php

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;

use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Document\Subscriber\OrderSubscriber;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\Content\Document\Subscriber\SubscriberTestCase;

class OrderSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new OrderSubscriber($this->encoder->reveal());
        $this->hydrateEvent->getDocument()->willReturn(new TestOrderDocument(10));
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
    }

    /**
     * It should set the order on the document.
     */
    public function testHydrateOrder()
    {
        $this->encoder->systemName('order')->willReturn('sys:order');
        $this->node->getPropertyValueWithDefault('sys:order', null)->willReturn(50);
        $this->accessor->set('suluOrder', 50)->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}

class TestOrderDocument implements OrderBehavior
{
    private $suluOrder;

    public function __construct($order)
    {
        $this->suluOrder = $order;
    }

    public function getSuluOrder()
    {
        return $this->suluOrder;
    }
}
