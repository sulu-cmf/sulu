<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;

class StructureSubscriberTest extends TestCase
{
    /**
     * @var ObjectProphecy<SearchManagerInterface>
     */
    private $searchManager;

    /**
     * @var StructureSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->subscriber = new StructureSubscriber($this->searchManager->reveal());
    }

    public function testIndexPersistedDocument(): void
    {
        $document = $this->prophesize(StructureBehavior::class);
        $persistEvent = $this->getPersistEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPersistedDocument($persistEvent->reveal());
    }

    public function testIndexPersistedDocumentUnsecuredDocument(): void
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn([]);

        $persistEvent = $this->getPersistEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPersistedDocument($persistEvent->reveal());
    }

    public function testIndexPersistedDocumentSecuredDocument(): void
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn(['some' => 'permissions']);

        $persistEvent = $this->getPersistEventMock($document->reveal());

        $this->searchManager->index($document)->shouldNotBeCalled();

        $this->subscriber->indexPersistedDocument($persistEvent->reveal());
    }

    public function testIndexPublishedDocument(): void
    {
        $document = $this->prophesize(StructureBehavior::class);
        $publishEvent = $this->getPublishEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPublishedDocument($publishEvent->reveal());
    }

    public function testIndexPublishedDocumentUnsecuredDocument(): void
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn([]);

        $publishEvent = $this->getPublishEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPublishedDocument($publishEvent->reveal());
    }

    public function testIndexPublishedDocumentSecuredDocument(): void
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn(['some' => 'permissions']);

        $publishEvent = $this->getPublishEventMock($document->reveal());

        $this->searchManager->index($document)->shouldNotBeCalled();

        $this->subscriber->indexPublishedDocument($publishEvent->reveal());
    }

    public function testIndexDocumentAfterRemoveDraft(): void
    {
        $removeDraftEvent = $this->prophesize(RemoveDraftEvent::class);
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(WorkflowStageBehavior::class);
        $removeDraftEvent->getDocument()->willReturn($document);

        $document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->searchManager->index($document)->shouldBeCalled();
        $document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();

        $this->subscriber->indexDocumentAfterRemoveDraft($removeDraftEvent->reveal());
    }

    public function testDeindexRemovedDocument(): void
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);

        $document = $this->prophesize(StructureBehavior::class);
        $removeEvent->getDocument()->willReturn($document);

        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->subscriber->deindexRemovedDocument($removeEvent->reveal());
    }

    public function testDeindexRemovedDocumentWithWorkflowStageBehavior(): void
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);

        $document = $this->prophesize(StructureBehavior::class)
            ->willImplement(WorkflowStageBehavior::class);
        $removeEvent->getDocument()->willReturn($document);

        $document->getWorkflowStage()->willReturn(WorkflowStage::TEST);

        $document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->searchManager->deindex($document)->shouldBeCalled();

        $document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->searchManager->deindex($document)->shouldBeCalled();

        $document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();

        $this->subscriber->deindexRemovedDocument($removeEvent->reveal());
    }

    public function testDeindexUnpublishedDocument(): void
    {
        $unpublishEvent = $this->prophesize(UnpublishEvent::class);

        $document = $this->prophesize(StructureBehavior::class);
        $unpublishEvent->getDocument()->willReturn($document->reveal());

        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->subscriber->deindexUnpublishedDocument($unpublishEvent->reveal());
    }

    public function testDeindexRemovedLocaleDocument(): void
    {
        $unpublishEvent = $this->prophesize(RemoveLocaleEvent::class);

        $document = $this->prophesize(StructureBehavior::class);
        $unpublishEvent->getDocument()->willReturn($document->reveal());

        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->subscriber->deindexRemovedLocaleDocument($unpublishEvent->reveal());
    }

    private function getPersistEventMock($document)
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $persistEvent->getDocument()->willReturn($document);

        return $persistEvent;
    }

    private function getPublishEventMock($document)
    {
        $publishEvent = $this->prophesize(PublishEvent::class);
        $publishEvent->getDocument()->willReturn($document);

        return $publishEvent;
    }
}
