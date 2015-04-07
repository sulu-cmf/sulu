<?php

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Core;

use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\Metadata;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Subscriber\Core\InstantiatorSubscriber;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Document\UndefinedDocument;

class InstantiatorSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const MIXIN_TYPES = 'jcr:mixinTypes';
    const PHPCR_TYPE = 'sulu:foobar';
    const ALIAS = 'alias';

    private $subscriber;

    public function setUp()
    {
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->subscriber = new InstantiatorSubscriber(
            $this->metadataFactory->reveal()
        );

        $this->metadata = $this->prophesize(Metadata::class);
        $this->hydrateEvent =  $this->prophesize(HydrateEvent::class);
        $this->createEvent = $this->prophesize(CreateEvent::class);
        $this->node = $this->prophesize(NodeInterface::class);
    }


    /**
     * It should create a document for a managed PHPCR node
     */
    public function testHandleHydrate()
    {
        $this->hydrateEvent->hasDocument()->willReturn(false);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->node->hasProperty(self::MIXIN_TYPES)->willReturn(true);
        $this->node->getPropertyValue(self::MIXIN_TYPES)->willReturn(array(
            self::PHPCR_TYPE
        ));
        $this->metadataFactory->hasMetadataForPhpcrType(self::PHPCR_TYPE)->willReturn(true);
        $this->metadataFactory->getMetadataForPhpcrType(self::PHPCR_TYPE)->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getClass()->willReturn('\stdClass');
        $this->hydrateEvent->setDocument(Argument::type('stdClass'))->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should create an UndefinedDocument for a not-managed PHPCR node
     */
    public function testHandleHydrateNotManaged()
    {
        $this->hydrateEvent->hasDocument()->willReturn(false);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->node->hasProperty(self::MIXIN_TYPES)->willReturn(false);
        $this->hydrateEvent->setDocument(Argument::type(UndefinedDocument::class))->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should create an UndefinedDocument for an not-mapped PHPCR node
     */
    public function testHandleHydrateNotMapped()
    {
        $this->hydrateEvent->hasDocument()->willReturn(false);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->node->hasProperty(self::MIXIN_TYPES)->willReturn(true);
        $this->node->getPropertyValue(self::MIXIN_TYPES)->willReturn(array(
            self::PHPCR_TYPE
        ));
        $this->metadataFactory->hasMetadataForPhpcrType(self::PHPCR_TYPE)->willReturn(false);
        $this->hydrateEvent->setDocument(Argument::type(UndefinedDocument::class))->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * If the document has already been set, do nothing
     */
    public function testHandleHydrateDocumentAlreadySet()
    {
        $this->hydrateEvent->hasDocument()->willReturn(true);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should create a new document
     */
    public function testHandleCreate()
    {
        $this->createEvent->getAlias()->willReturn(self::ALIAS);
        $this->metadataFactory->getMetadataForAlias(self::ALIAS)->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getClass()->willReturn('\stdClass');
        $this->createEvent->setDocument(Argument::type('stdClass'))->shouldBeCalled();

        $this->subscriber->handleCreate($this->createEvent->reveal());
    }
}
