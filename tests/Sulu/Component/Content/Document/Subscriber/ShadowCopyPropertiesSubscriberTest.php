<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\PropertyInterface;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ShadowCopyPropertiesSubscriberTest extends SubscriberTestCase
{
    /**
     * @var ShadowCopyPropertiesSubscriber
     */
    private $subscriber;

    /**
     * @var object
     */
    private $document;

    public function setUp()
    {
        parent::setUp();

        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->persistEvent->getNode()->willReturn($this->node);
        $this->document = $this->prophesize(TestShadowDocumentInterface::class);

        $this->subscriber = new ShadowCopyPropertiesSubscriber($this->encoder->reveal());
    }

    public function testCopyToShadows()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('i18n:de-shadow-base');
        $property->getValue()->willReturn('en');

        $this->document->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();

        $this->node->getProperties('i18n:*-shadow-base')->willReturn([$property->reveal()]);

        $this->subscriber->copyToShadows($this->document->reveal(), $this->node->reveal());
    }

    public function testCopyToShadowsMultiple()
    {
        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('i18n:de-shadow-base');
        $property1->getValue()->willReturn('en');

        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('i18n:en_us-shadow-base');
        $property2->getValue()->willReturn('en');

        $this->document->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();

        $this->node->setProperty('i18n:en_us-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-excerpt-categories', [3, 2, 1])->shouldBeCalled();

        $this->node->getProperties('i18n:*-shadow-base')->willReturn([$property1->reveal(), $property2->reveal()]);

        $this->subscriber->copyToShadows($this->document->reveal(), $this->node->reveal());
    }

    public function testCopyFromShadow()
    {
        $this->document->getShadowLocale()->willReturn('en');
        $this->document->getLocale()->willReturn('de');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();

        $this->subscriber->copyFromShadow($this->document->reveal(), $this->node->reveal());
    }

    public function testHandlePersistShadow()
    {
        $this->document->isShadowLocaleEnabled()->willReturn(true);

        $this->document->getShadowLocale()->willReturn('en');
        $this->document->getLocale()->willReturn('de');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();

        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHandlePersistNotShadow()
    {
        $this->document->isShadowLocaleEnabled()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('i18n:de-shadow-base');
        $property->getValue()->willReturn('en');

        $this->document->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();

        $this->node->getProperties('i18n:*-shadow-base')->willReturn([$property->reveal()]);

        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}

interface TestShadowDocumentInterface extends ShadowLocaleBehavior, LocaleBehavior
{
}
