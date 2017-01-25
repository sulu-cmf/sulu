<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\BlameBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedBlameBehavior;
use Sulu\Component\Content\Document\Subscriber\BlameSubscriber;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class BlameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var HydrateEvent
     */
    private $hydrateEvent;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var DocumentAccessor
     */
    private $accessor;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var BlameSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->subscriber = new BlameSubscriber($this->propertyEncoder->reveal());

        $this->persistEvent->getNode()->willReturn($this->node);
        $this->persistEvent->getAccessor()->willReturn($this->accessor);
        $this->persistEvent->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system_localized', 'creator', 'de')->willReturn('i18n:de-creator');
        $this->propertyEncoder->encode('system_localized', 'changer', 'de')->willReturn('i18n:de-changer');
        $this->propertyEncoder->encode('system', 'creator', 'de')->willReturn('creator');
        $this->propertyEncoder->encode('system', 'changer', 'de')->willReturn('changer');
    }

    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn(new \stdClass());
        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->node->setProperty(Argument::cetera())->shouldNotBeCalled();
        $this->subscriber->setBlamesOnNodeForPersist($this->persistEvent->reveal());
    }

    public function testPersistLocaleIsNull()
    {
        $document = $this->prophesize(LocalizedBlameBehavior::class);
        $this->persistEvent->getLocale()->willReturn(null);
        $this->persistEvent->getDocument()->willReturn($document->reveal());
        $this->persistEvent->getOption('user')->willReturn(1);
        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->node->setProperty()->shouldNotBeCalled();

        $this->subscriber->setBlamesOnNodeForPersist($this->persistEvent->reveal());
    }

    public function testPersistCreatorWhenNull()
    {
        $document = $this->prophesize(LocalizedBlameBehavior::class);
        $document->getCreator()->willReturn(null);

        $this->persistEvent->getDocument()->willReturn($document->reveal());
        $this->persistEvent->getOption('user')->willReturn(2);

        $this->node->hasProperty('i18n:de-creator')->willReturn(false);

        $this->accessor->set('creator', 2)->shouldBeCalled();
        $this->node->setProperty('i18n:de-creator', Argument::any())->shouldBeCalled();
        $this->accessor->set('changer', 2)->shouldBeCalled();
        $this->node->setProperty('i18n:de-changer', 2)->shouldBeCalled();

        $this->subscriber->setBlamesOnNodeForPersist($this->persistEvent->reveal());
    }

    public function testPersistChanger()
    {
        $document = $this->prophesize(LocalizedBlameBehavior::class);
        $document->getCreator()->willReturn(1);

        $this->node->hasProperty('i18n:de-creator')->willReturn(true);

        $this->persistEvent->getDocument()->willReturn($document->reveal());
        $this->persistEvent->getOption('user')->willReturn(2);
        $this->accessor->set('changer', 2)->shouldBeCalled();
        $this->node->setProperty('i18n:de-changer', 2)->shouldBeCalled();
        $this->accessor->set('creator', Argument::any())->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-creator', Argument::any())->shouldNotBeCalled();

        $this->subscriber->setBlamesOnNodeForPersist($this->persistEvent->reveal());
    }

    public function testPersistChangerWithoutLocalization()
    {
        $document = $this->prophesize(BlameBehavior::class);
        $document->getCreator()->willReturn(1);

        $this->node->hasProperty('creator')->willReturn(true);

        $this->persistEvent->getDocument()->willReturn($document->reveal());
        $this->persistEvent->getOption('user')->willReturn(2);
        $this->accessor->set('changer', 2)->shouldBeCalled();
        $this->node->setProperty('changer', 2)->shouldBeCalled();
        $this->accessor->set('creator', Argument::any())->shouldNotBeCalled();
        $this->node->setProperty('creator', Argument::any())->shouldNotBeCalled();

        $this->subscriber->setBlamesOnNodeForPersist($this->persistEvent->reveal());
    }

    public function testPublish()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getLocale()->willReturn('de');

        $document = $this->prophesize(LocalizedBlameBehavior::class);
        $document->getCreator()->willReturn(null);
        $document->getChanger()->willReturn(2);
        $event->getDocument()->willReturn($document->reveal());

        $this->node->hasProperty('i18n:de-creator')->willReturn(false);
        $event->getNode()->willReturn($this->node->reveal());

        $event->getAccessor()->willReturn($this->accessor->reveal());

        $this->accessor->set('changer', 2)->shouldBeCalled();
        $this->node->setProperty('i18n:de-changer', 2)->shouldBeCalled();
        $this->accessor->set('creator', 2)->shouldBeCalled();
        $this->node->setProperty('i18n:de-creator', Argument::any())->shouldBeCalled();
        $this->subscriber->setBlamesOnNodeForPublish($event->reveal());
    }

    public function testPublishWithoutLocalization()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getLocale()->willReturn('de');

        $document = $this->prophesize(BlameBehavior::class);
        $document->getCreator()->willReturn(null);
        $document->getChanger()->willReturn(2);
        $event->getDocument()->willReturn($document->reveal());

        $this->node->hasProperty('creator')->willReturn(false);
        $event->getNode()->willReturn($this->node->reveal());

        $event->getAccessor()->willReturn($this->accessor->reveal());

        $this->accessor->set('changer', 2)->shouldBeCalled();
        $this->node->setProperty('changer', 2)->shouldBeCalled();
        $this->accessor->set('creator', 2)->shouldBeCalled();
        $this->node->setProperty('creator', Argument::any())->shouldBeCalled();
        $this->subscriber->setBlamesOnNodeForPublish($event->reveal());
    }

    public function testPublishOnlyChanger()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getLocale()->willReturn('de');

        $document = $this->prophesize(LocalizedBlameBehavior::class);
        $document->getCreator()->willReturn(1);
        $document->getChanger()->willReturn(2);
        $event->getDocument()->willReturn($document->reveal());

        $this->node->hasProperty('i18n:de-creator')->willReturn(true);
        $event->getNode()->willReturn($this->node->reveal());

        $event->getAccessor()->willReturn($this->accessor->reveal());

        $this->accessor->set('changer', 2)->shouldBeCalled();
        $this->node->setProperty('i18n:de-changer', 2)->shouldBeCalled();
        $this->accessor->set('creator', 2)->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-creator', Argument::any())->shouldNotBeCalled();
        $this->subscriber->setBlamesOnNodeForPublish($event->reveal());
    }

    public function testPublishNonBlameBehavior()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getAccessor()->willReturn($this->accessor->reveal());
        $event->getNode()->willReturn($this->node->reveal());
        $event->getDocument()->willReturn(new \stdClass());
        $this->persistEvent->getOption('user')->willReturn(2);

        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->node->setProperty(Argument::cetera())->shouldNotBeCalled();
        $this->subscriber->setBlamesOnNodeForPublish($event->reveal());
    }

    public function testRestore()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $event->getLocale()->willReturn('de');
        $event->getOption('user')->willReturn(2);
        $event->getNode()->willReturn($this->node->reveal());

        $document = $this->prophesize(LocalizedBlameBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->node->setProperty('i18n:de-changer', 2)->shouldBeCalled();
        $this->subscriber->setChangerForRestore($event->reveal());
    }

    public function testRestoreWithoutLocale()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $event->getLocale()->willReturn('de');
        $event->getOption('user')->willReturn(2);

        $event->getNode()->willReturn($this->node->reveal());

        $document = $this->prophesize(BlameBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->node->setProperty('changer', 2)->shouldBeCalled();
        $this->subscriber->setChangerForRestore($event->reveal());
    }

    public function testRestoreNonBlameSubscriber()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $event->getDocument()->willReturn(new \stdClass());
        $event->getNode()->willReturn($this->node->reveal());

        $this->node->setProperty(Argument::cetera())->shouldNotBeCalled();
        $this->subscriber->setChangerForRestore($event->reveal());
    }
}
