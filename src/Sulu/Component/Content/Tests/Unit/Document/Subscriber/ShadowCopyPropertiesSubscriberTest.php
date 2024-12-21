<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\PropertyInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Subscriber\ShadowCopyPropertiesSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ShadowCopyPropertiesSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /**
     * @var ShadowCopyPropertiesSubscriber
     */
    private $subscriber;

    /**
     * @var ObjectProphecy<TestShadowDocumentInterface>
     */
    private $document;

    public function setUp(): void
    {
        parent::setUp();

        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->persistEvent->getNode()->willReturn($this->node);
        $this->document = $this->prophesize(TestShadowDocumentInterface::class);

        $this->subscriber = new ShadowCopyPropertiesSubscriber($this->encoder->reveal());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                'sulu_document_manager.persist' => ['copyShadowProperties', -256],
                'sulu_document_manager.publish' => ['copyShadowProperties', -256],
            ],
            ShadowCopyPropertiesSubscriber::getSubscribedEvents()
        );
    }

    public function testCopyToShadows(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('i18n:de-shadow-base');
        $property->getValue()->willReturn('en');

        $this->document->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:de-shadow-on', false)->willReturn(true);

        $authored = new \DateTime('now');
        $lastModified = new \DateTime('now');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);
        $this->node->getPropertyValueWithDefault('i18n:en-navContexts', [])->willReturn(['main']);
        $this->node->getPropertyValueWithDefault('i18n:en-author', null)->willReturn(12);
        $this->node->getPropertyValueWithDefault('i18n:en-authored', null)->willReturn($authored);
        $this->node->getPropertyValueWithDefault('i18n:en-lastModified', null)->willReturn($lastModified);
        $this->node->getPropertyValueWithDefault('i18n:en-template', null)->willReturn('test');

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();
        $this->node->setProperty('i18n:de-navContexts', ['main'])->shouldBeCalled();
        $this->node->setProperty('i18n:de-author', 12)->shouldBeCalled();
        $this->node->setProperty('i18n:de-authored', $authored)->shouldBeCalled();
        $this->node->setProperty('i18n:de-lastModified', $lastModified)->shouldBeCalled();
        $this->node->setProperty('i18n:de-template', 'test')->shouldBeCalled();

        $this->node->getProperties('i18n:*-shadow-base')->willReturn(['i18n:de-shadow-base' => $property->reveal()]);

        $this->subscriber->copyToShadows($this->document->reveal(), $this->node->reveal());
    }

    public function testCopyToShadowsNoShadow(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('i18n:de-shadow-base');
        $property->getValue()->willReturn('en');

        $this->document->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:de-shadow-on', false)->willReturn(false);

        $authored = new \DateTime('now');
        $lastModified = new \DateTime('now');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);
        $this->node->getPropertyValueWithDefault('i18n:en-navContexts', [])->willReturn(['main']);
        $this->node->getPropertyValueWithDefault('i18n:en-author', null)->willReturn(12);
        $this->node->getPropertyValueWithDefault('i18n:en-authored', null)->willReturn($authored);
        $this->node->getPropertyValueWithDefault('i18n:en-lastModified', null)->willReturn($lastModified);
        $this->node->getPropertyValueWithDefault('i18n:en-template', null)->willReturn('test');

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-navContexts', ['main'])->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-author', 12)->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-authored', $authored)->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-lastModified', $lastModified)->shouldNotBeCalled();
        $this->node->setProperty('i18n:de-template', 'test')->shouldNotBeCalled();

        $this->node->getProperties('i18n:*-shadow-base')->willReturn(['i18n:de-shadow-base' => $property->reveal()]);

        $this->subscriber->copyToShadows($this->document->reveal(), $this->node->reveal());
    }

    public function testCopyToShadowsMultiple(): void
    {
        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('i18n:de-shadow-base');
        $property1->getValue()->willReturn('en');

        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('i18n:en_us-shadow-base');
        $property2->getValue()->willReturn('en');

        $this->document->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:de-shadow-on', false)->willReturn(true);
        $this->node->getPropertyValueWithDefault('i18n:en_us-shadow-on', false)->willReturn(true);

        $authored = new \DateTime('now');
        $lastModified = new \DateTime('now');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);
        $this->node->getPropertyValueWithDefault('i18n:en-navContexts', [])->willReturn(['main']);
        $this->node->getPropertyValueWithDefault('i18n:en-author', null)->willReturn(12);
        $this->node->getPropertyValueWithDefault('i18n:en-authored', null)->willReturn($authored);
        $this->node->getPropertyValueWithDefault('i18n:en-lastModified', null)->willReturn($lastModified);
        $this->node->getPropertyValueWithDefault('i18n:en-template', null)->willReturn('test');

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();
        $this->node->setProperty('i18n:de-navContexts', ['main'])->shouldBeCalled();
        $this->node->setProperty('i18n:de-author', 12)->shouldBeCalled();
        $this->node->setProperty('i18n:de-authored', $authored)->shouldBeCalled();
        $this->node->setProperty('i18n:de-lastModified', $lastModified)->shouldBeCalled();
        $this->node->setProperty('i18n:de-template', 'test')->shouldBeCalled();

        $this->node->setProperty('i18n:en_us-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-excerpt-categories', [3, 2, 1])->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-navContexts', ['main'])->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-author', 12)->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-authored', $authored)->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-lastModified', $lastModified)->shouldBeCalled();
        $this->node->setProperty('i18n:en_us-template', 'test')->shouldBeCalled();

        $this->node->getProperties('i18n:*-shadow-base')->willReturn(['i18n:de-shadow-base' => $property1->reveal(), 'i18n:en_us-shadow-base' => $property2->reveal()]);

        $this->subscriber->copyToShadows($this->document->reveal(), $this->node->reveal());
    }

    public function testCopyFromShadow(): void
    {
        $this->document->getShadowLocale()->willReturn('en');
        $this->document->getLocale()->willReturn('de');

        $authored = new \DateTime('now');
        $lastModified = new \DateTime('now');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);
        $this->node->getPropertyValueWithDefault('i18n:en-navContexts', [])->willReturn(['main']);
        $this->node->getPropertyValueWithDefault('i18n:en-author', null)->willReturn(12);
        $this->node->getPropertyValueWithDefault('i18n:en-authored', null)->willReturn($authored);
        $this->node->getPropertyValueWithDefault('i18n:en-lastModified', null)->willReturn($lastModified);
        $this->node->getPropertyValueWithDefault('i18n:en-template', null)->willReturn('test');

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();
        $this->node->setProperty('i18n:de-navContexts', ['main'])->shouldBeCalled();
        $this->node->setProperty('i18n:de-author', 12)->shouldBeCalled();
        $this->node->setProperty('i18n:de-authored', $authored)->shouldBeCalled();
        $this->node->setProperty('i18n:de-lastModified', $lastModified)->shouldBeCalled();
        $this->node->setProperty('i18n:de-template', 'test')->shouldBeCalled();

        $this->subscriber->copyFromShadow($this->document->reveal(), $this->node->reveal());
    }

    public function testHandlePersistShadow(): void
    {
        $this->document->isShadowLocaleEnabled()->willReturn(true);

        $this->document->getShadowLocale()->willReturn('en');
        $this->document->getLocale()->willReturn('de');

        $authored = new \DateTime('now');
        $lastModified = new \DateTime('now');

        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-tags', [])->willReturn([1, 2, 3]);
        $this->node->getPropertyValueWithDefault('i18n:en-excerpt-categories', [])->willReturn([3, 2, 1]);
        $this->node->getPropertyValueWithDefault('i18n:en-navContexts', [])->willReturn(['main']);
        $this->node->getPropertyValueWithDefault('i18n:en-author', null)->willReturn(12);
        $this->node->getPropertyValueWithDefault('i18n:en-authored', null)->willReturn($authored);
        $this->node->getPropertyValueWithDefault('i18n:en-lastModified', null)->willReturn($lastModified);
        $this->node->getPropertyValueWithDefault('i18n:en-template', null)->willReturn('test');

        $this->node->setProperty('i18n:de-excerpt-tags', [1, 2, 3])->shouldBeCalled();
        $this->node->setProperty('i18n:de-excerpt-categories', [3, 2, 1])->shouldBeCalled();
        $this->node->setProperty('i18n:de-navContexts', ['main'])->shouldBeCalled();
        $this->node->setProperty('i18n:de-author', 12)->shouldBeCalled();
        $this->node->setProperty('i18n:de-authored', $authored)->shouldBeCalled();
        $this->node->setProperty('i18n:de-lastModified', $lastModified)->shouldBeCalled();
        $this->node->setProperty('i18n:de-template', 'test')->shouldBeCalled();

        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());

        $this->subscriber->copyShadowProperties($this->persistEvent->reveal());
    }
}

interface TestShadowDocumentInterface extends ShadowLocaleBehavior, LocaleBehavior
{
}
