<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\PageBundle\Document\Subscriber\PublishSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeHelperInterface;

class PublishSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $liveSession;

    /**
     * @var ObjectProphecy<NodeHelperInterface>
     */
    private $nodeHelper;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var PublishSubscriber
     */
    private $publishSubscriber;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    public function setUp(): void
    {
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->nodeHelper = $this->prophesize(NodeHelperInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);

        $this->publishSubscriber = new PublishSubscriber(
            $this->liveSession->reveal(),
            $this->nodeHelper->reveal(),
            $this->propertyEncoder->reveal(),
            $this->metadataFactory->reveal()
        );

        $this->node = $this->prophesize(NodeInterface::class);
    }

    public function testCreateNodeInPublicWorkspaceWithNewNode(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());

        $mixinNodeType1 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType1->getName()->willReturn('mixin1');
        $mixinNodeType2 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType2->getName()->willReturn('mixin2');

        $defaultRootNode = $this->prophesize(NodeInterface::class);
        $defaultCmfNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode->getIdentifier()->willReturn('uuid');
        $defaultCmfNode->getNode('sulu')->willReturn($defaultSuluNode->reveal());
        $defaultRootNode->getNode('cmf')->willReturn($defaultCmfNode->reveal());
        $session = $this->prophesize(SessionInterface::class);
        $session->getRootNode()->willReturn($defaultRootNode->reveal());

        $liveRootNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode->hasNode('sulu')->willReturn(false);
        $liveRootNode->getNode('cmf')->willReturn($liveCmfNode);
        $liveRootNode->hasNode('cmf')->willReturn(true);
        $this->liveSession->getRootNode()->willReturn($liveRootNode->reveal());

        $liveSuluNode = $this->prophesize(NodeInterface::class);
        $liveSuluNode->setMixins(['mix:referenceable'])->shouldBeCalled();
        $liveSuluNode->setProperty('jcr:uuid', 'uuid')->shouldBeCalled();
        $liveCmfNode->addNode('sulu')->willReturn($liveSuluNode->reveal());

        $this->node->isNew()->willReturn(true);
        $this->node->getPath()->willReturn('/cmf/sulu');
        $this->node->getSession()->willReturn($session->reveal());

        $this->liveSession->itemExists('/cmf/sulu')->willReturn(false);

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testCreateNodeInPublicWorkspaceWithOldNodeAndDifferentName(): void
    {
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->getName()->willReturn('cmf');
        $liveNode->rename('cmf-test')->shouldBeCalled();

        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf');

        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $this->node->getName()->willReturn('cmf-test');
        $this->node->getPath()->willReturn('/cmf-test');
        $this->node->isNew()->willReturn(false);

        $this->liveSession->getNode('/cmf')->willReturn($liveNode);
        $this->liveSession->getRootNode()->shouldNotBeCalled();

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testCreateNodeInPublicWorkspaceWithOldNodeAndSameName(): void
    {
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->getName()->willReturn('cmf');
        $liveNode->rename(Argument::cetera())->shouldNotBeCalled();

        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf');

        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $this->node->getName()->willReturn('cmf');
        $this->node->getPath()->willReturn('/cmf');
        $this->node->isNew()->willReturn(false);

        $this->liveSession->getNode('/cmf')->willReturn($liveNode);
        $this->liveSession->getRootNode()->shouldNotBeCalled();

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testCreateNodeInPublicWorkspaceWithExistingNode(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());

        $this->node->getPath()->willReturn('/cmf');
        $this->node->isNew()->willReturn(true);

        $this->liveSession->itemExists('/cmf')->willReturn(true);
        $this->liveSession->getRootNode()->shouldNotBeCalled();

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testRemoveNodeFromPublicWorkspace(): void
    {
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getSyncRemoveLive()->willReturn(true);

        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $this->metadataFactory->getMetadataForClass(\get_class($document->reveal()))->willReturn($metadata->reveal());

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $this->node->remove()->shouldBeCalled();

        $this->publishSubscriber->removeNodeFromPublicWorkspace($event->reveal());
    }

    public function testRemoveNodeFromPublicWorkspaceMetadata(): void
    {
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getSyncRemoveLive()->willReturn(false);

        $document = $this->prophesize(PathBehavior::class);

        $this->metadataFactory->getMetadataForClass(\get_class($document->reveal()))->willReturn($metadata->reveal());

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode(Argument::any())->shouldNotBeCalled();

        $this->publishSubscriber->removeNodeFromPublicWorkspace($event->reveal());
    }

    public function testRemoveLocalePropertiesFromPublicWorkspace(): void
    {
        /** @var ObjectProphecy|PathBehavior $document */
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()
            ->willReturn('/cmf/sulu');

        /** @var ObjectProphecy|NodeInterface $node */
        $node = $this->prophesize(NodeInterface::class);
        /** @var ObjectProphecy|NodeInterface $liveNode */
        $liveNode = $this->prophesize(NodeInterface::class);
        /** @var ObjectProphecy|RemoveLocaleEvent $event */
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $event->getDocument()
            ->willReturn($document->reveal());
        $event->getLocale()
            ->willReturn('de');
        $event->getNode()
            ->willReturn($node->reveal());

        $this->liveSession->getNode('/cmf/sulu')
            ->shouldBeCalled()
            ->willReturn($liveNode->reveal());

        $properties = [];

        for ($i = 0; $i < 10; ++$i) {
            /** @var PropertyInterface|ObjectProphecy $property */
            $property = $this->prophesize(PropertyInterface::class);
            $property->remove()->shouldBeCalled();

            $properties[] = $property;
        }

        $node->getProperties('i18n:de-*')
            ->shouldBeCalled()
            ->willReturn(\array_slice($properties, 0, 5));

        $liveNode->getProperties('i18n:de-*')
            ->shouldBeCalled()
            ->willReturn(\array_slice($properties, 5, 5));

        $this->propertyEncoder->localizedSystemName('', 'de')
            ->shouldBeCalledTimes(2)
            ->willReturn('i18n:de-');

        $this->propertyEncoder->localizedContentName('', 'de')
            ->shouldBeCalledTimes(2)
            ->willReturn('i18n:de-');

        $this->publishSubscriber->removeLocalePropertiesFromPublicWorkspace($event->reveal());
    }

    public function testMoveNodeInPublicWorkspace(): void
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getDestId()->willReturn('uuid');
        $event->getDestName()->willReturn('name');

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $this->nodeHelper->move($this->node, 'uuid', 'name')->shouldBeCalled();

        $this->publishSubscriber->moveNodeInPublicWorkspace($event->reveal());
    }

    public function testCopyNodeInPublicWorkspace(): void
    {
        $this->node->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(CopyEvent::class);
        $event->getCopiedNode()->willReturn($this->node->reveal());

        $mixinNodeType1 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType1->getName()->willReturn('mixin1');
        $mixinNodeType2 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType2->getName()->willReturn('mixin2');

        $defaultRootNode = $this->prophesize(NodeInterface::class);
        $defaultCmfNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode->getIdentifier()->willReturn('uuid');
        $defaultCmfNode->getNode('sulu')->willReturn($defaultSuluNode->reveal());
        $defaultRootNode->getNode('cmf')->willReturn($defaultCmfNode->reveal());
        $session = $this->prophesize(SessionInterface::class);
        $session->getRootNode()->willReturn($defaultRootNode->reveal());
        $this->node->getSession()->willReturn($session->reveal());

        $liveRootNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode->hasNode('sulu')->willReturn(false);
        $liveRootNode->getNode('cmf')->willReturn($liveCmfNode);
        $liveRootNode->hasNode('cmf')->willReturn(true);
        $this->liveSession->getRootNode()->willReturn($liveRootNode->reveal());

        $liveSuluNode = $this->prophesize(NodeInterface::class);
        $liveSuluNode->setMixins(['mix:referenceable'])->shouldBeCalled();
        $liveSuluNode->setProperty('jcr:uuid', 'uuid')->shouldBeCalled();
        $liveCmfNode->addNode('sulu')->willReturn($liveSuluNode->reveal());

        $this->liveSession->itemExists('/cmf/sulu')->willReturn(false);

        $defaultTestNode = $this->prophesize(NodeInterface::class);
        $defaultTestNode->getPath()->willReturn('/cmf/sulu/test');
        $defaultTestNode->getSession()->willReturn($session->reveal());
        $defaultSuluNode->getNode('test')->willReturn($defaultTestNode);
        $defaultTestNode->getIdentifier()->willReturn('test-uuid');
        $defaultTestNode->getNodes()->willReturn([]);
        $this->node->getNodes()->willReturn([$defaultTestNode->reveal()]);

        $liveSuluNode->hasNode('test')->willReturn(false);
        $liveTestNode = $this->prophesize(NodeInterface::class);
        $liveTestNode->setMixins(['mix:referenceable'])->shouldBeCalled();
        $liveTestNode->setProperty('jcr:uuid', 'test-uuid')->shouldBeCalled();
        $liveSuluNode->addNode('test')->willReturn($liveTestNode->reveal());

        $this->liveSession->itemExists('/cmf/sulu/test')->willReturn(false);

        $this->publishSubscriber->copyNodeInPublicWorkspace($event->reveal());
    }

    public function testReorderInPublicWorkspace(): void
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu_io/contents/page');

        $event = $this->prophesize(ReorderEvent::class);
        $event->getDestId()->willReturn('uuid');
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu_io/contents/page')->willReturn($this->node->reveal());

        $parentNode = $this->prophesize(NodeInterface::class);
        $this->node->getParent()->willReturn($parentNode->reveal());

        $siblingNode = $this->prophesize(NodeInterface::class);
        $parentNode->getNodes()->willReturn([$this->node->reveal(), $siblingNode->reveal()]);

        $this->propertyEncoder->systemName('order')->willReturn('sulu:order');

        $this->nodeHelper->reorder($this->node->reveal(), 'uuid')->shouldBeCalled();

        $this->node->setProperty('sulu:order', 10)->shouldBeCalled();
        $siblingNode->setProperty('sulu:order', 20)->shouldBeCalled();

        $this->publishSubscriber->reorderNodeInPublicWorkspace($event->reveal());
    }

    public function testSetNodeFromPublicWorkspaceForPublishing(): void
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(PublishEvent::class);
        $event->hasNode()->willReturn(false);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $event->setNode($this->node->reveal())->shouldBeCalled();

        $this->publishSubscriber->setNodeFromPublicWorkspaceForPublishing($event->reveal());
    }

    public function testSetNodeFromPublicWorkspaceForPublishingAlreadySet(): void
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(PublishEvent::class);
        $event->hasNode()->willReturn(true);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->shouldNotBeCalled();

        $event->setNode(Argument::any())->shouldNotBeCalled();

        $this->publishSubscriber->setNodeFromPublicWorkspaceForPublishing($event->reveal());
    }

    public function testSetNodeFromPublicWorkspaceForUnpublishing(): void
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(UnpublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $event->setNode($this->node->reveal())->shouldBeCalled();

        $this->publishSubscriber->setNodeFromPublicWorkspaceForUnpublishing($event->reveal());
    }

    public function testFlushPublicWorkspace(): void
    {
        $this->liveSession->save()->shouldBeCalled();

        $this->publishSubscriber->flushPublicWorkspace();
    }
}
