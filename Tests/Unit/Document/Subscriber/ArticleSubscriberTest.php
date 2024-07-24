<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\ArticleSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;

class ArticleSubscriberTest extends TestCase
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var IndexerInterface
     */
    private $liveIndexer;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ArticleSubscriber
     */
    private $articleSubscriber;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var ArticleDocument
     */
    private $document;

    /**
     * @var string
     */
    private $uuid = '123-123-123';

    /**
     * @var string
     */
    private $locale = 'de';

    public function setUp(): void
    {
        $this->indexer = $this->prophesize(IndexerInterface::class);
        $this->liveIndexer = $this->prophesize(IndexerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->document = $this->prophesize(ArticleDocument::class);
        $this->document->getUuid()->willReturn($this->uuid);
        $this->document->getLocale()->willReturn($this->locale);
        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());

        $this->articleSubscriber = new ArticleSubscriber(
            $this->indexer->reveal(),
            $this->liveIndexer->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->propertyEncoder->reveal()
        );
    }

    protected function prophesizeEvent($className, $locale = null, $options = null)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());

        if (null !== $options) {
            $event->getOptions()->willReturn($options);
        }

        if (null !== $locale) {
            $event->getLocale()->willReturn($locale);
        }

        return $event->reveal();
    }

    public function testHandleScheduleIndex()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndex($event);

        $this->indexer->index(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleScheduleIndexLive()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndexLive($event);

        $this->indexer->index(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleFlush()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndex($event);

        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());
        $this->documentManager->refresh($this->document->reveal())->willReturn($this->document->reveal());

        $this->articleSubscriber->handleFlush($this->prophesize(FlushEvent::class)->reveal());

        $this->indexer->index($this->document->reveal())->shouldBeCalled();
        $this->indexer->flush()->shouldBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleFlushLive()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndexLive($event);

        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());
        $this->documentManager->refresh($this->document->reveal())->willReturn($this->document->reveal());

        $this->articleSubscriber->handleFlushLive($this->prophesize(FlushEvent::class)->reveal());

        $this->indexer->index(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->index($this->document->reveal())->shouldBeCalled();
        $this->liveIndexer->flush()->shouldBeCalled();
    }

    public function testHandleRemove()
    {
        $this->articleSubscriber->handleRemove($this->prophesizeEvent(RemoveEvent::class));

        $this->indexer->remove($this->document->reveal())->shouldBeCalled();
        $this->indexer->flush()->shouldBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleRemoveLive()
    {
        $this->articleSubscriber->handleRemoveLive($this->prophesizeEvent(RemoveEvent::class));

        $this->indexer->remove(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->remove($this->document->reveal())->shouldBeCalled();
        $this->liveIndexer->flush()->shouldBeCalled();
    }

    public function testPublishChildren()
    {
        $children = [
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
        ];

        $this->document->getChildren()->willReturn(new \ArrayIterator($children));

        foreach ($children as $child) {
            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
            $this->documentManager->publish($child, $this->locale)->shouldBeCalled();
        }

        $this->articleSubscriber->publishChildren($this->prophesizeEvent(PublishEvent::class, $this->locale));
    }

    public function testSynchronizeChildren()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123'),
            $this->createNodeMock('456-456-456'),
        ];

        $liveNode->getNodes()->willReturn($children);
        $draftNode->getNodes()->willReturn($children);

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    public function testSynchronizeChildrenDifferenceRemove()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123'),
            $this->createNodeMock('456-456-456', true),
        ];

        $liveNode->getNodes()->willReturn($children);
        $draftNode->getNodes()->willReturn([$children[0]]);

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    public function testSynchronizeChildrenDifferenceAdd()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123'),
            $this->createNodeMock('456-456-456'),
        ];

        $liveNode->getNodes()->willReturn([$children[0]]);
        $draftNode->getNodes()->willReturn($children);

        // nothing should happen because publish new children will be done somewhere else

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    public function testSynchronizeChildrenDifferenceAddAndRemove()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123', true),
            $this->createNodeMock('456-456-456'),
        ];

        $liveNode->getNodes()->willReturn([$children[0]]);
        $draftNode->getNodes()->willReturn([$children[1]]);

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    private function createNodeMock($uuid, $removeCall = false)
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIdentifier()->willReturn($uuid);

        if ($removeCall) {
            $node->remove()->shouldBeCalled();
        } else {
            $node->remove()->shouldNotBeCalled();
        }

        return $node->reveal();
    }

    public function testRemoveDraftChildren()
    {
        $children = [
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
        ];

        $this->document->getChildren()->willReturn($children);

        foreach ($children as $child) {
            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
            $this->documentManager->removeDraft($child, $this->locale)->shouldBeCalled();
        }

        $this->articleSubscriber->removeDraftChildren($this->prophesizeEvent(RemoveDraftEvent::class, $this->locale));
    }

    public function testRemoveDraftChildrenNotExists()
    {
        $child = $this->prophesize(ArticlePageDocument::class)->reveal();

        $this->document->getChildren()->willReturn([$child]);

        $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
        $this->documentManager->removeDraft($child, $this->locale)->shouldBeCalled()
            ->willThrow(new PathNotFoundException());

        $node = $this->prophesize(NodeInterface::class);
        $node->remove()->shouldBeCalled();
        $this->documentInspector->getNode($child)->willReturn($node->reveal());

        $this->articleSubscriber->removeDraftChildren($this->prophesizeEvent(RemoveDraftEvent::class, $this->locale));
    }

    public function testHandleChildrenPersistWithoutChanges()
    {
        $children = [];

        for ($id = 1; $id < 4; ++$id) {
            $child = $this->prophesize(ArticlePageDocument::class);
            $child->getUuid()->willReturn($id);
            $child->getLocale()->willReturn($this->locale);
            $child->getShadowLocale()->willReturn(null);
            $child->isShadowLocaleEnabled()->willReturn(false);
            $child->getStructureType()->willReturn('my-other-test');
            $child->setStructureType('my-test')->shouldBeCalled();

            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);

            $this->documentManager->persist(
                $child->reveal(),
                $this->locale,
                [
                    'clear_missing_content' => false,
                    'auto_name' => false,
                    'auto_rename' => false,
                ]
            )->shouldBeCalled();

            $children[] = $child;
        }

        $this->document->getStructureType()->willReturn('my-test');
        $this->document->getChildren()->willReturn($children);
        $this->document->getShadowLocale()->willReturn(null);
        $this->document->isShadowLocaleEnabled()->willReturn(false);

        $this->articleSubscriber->handleChildrenPersist(
            $this->prophesizeEvent(PersistEvent::class, $this->locale, [])
        );
    }

    public function testHandleChildrenPersistWithStructureTypeChange()
    {
        $children = [];

        for ($id = 1; $id < 4; ++$id) {
            /** @var ArticlePageDocument $child */
            $child = $this->prophesize(ArticlePageDocument::class);
            $child->getUuid()->willReturn($id);
            $child->getLocale()->willReturn($this->locale);
            $child->getShadowLocale()->willReturn(null);
            $child->isShadowLocaleEnabled()->willReturn(false);
            $child->getStructureType()->willReturn('old');
            $child->setStructureType('changed-structure')->shouldBeCalled();

            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);

            $this->documentManager->persist(
                $child,
                $this->locale,
                [
                    'clear_missing_content' => false,
                    'auto_name' => false,
                    'auto_rename' => false,
                ]
            )->shouldBeCalled();

            $children[] = $child;
        }

        $this->document->getStructureType()->willReturn('changed-structure');
        $this->document->getChildren()->willReturn($children);
        $this->document->getShadowLocale()->willReturn(null);
        $this->document->isShadowLocaleEnabled()->willReturn(false);

        $this->articleSubscriber->handleChildrenPersist(
            $this->prophesizeEvent(PersistEvent::class, $this->locale, [])
        );
    }

    public function testHandleChildrenPersistShadow()
    {
        $children = [];

        for ($id = 1; $id < 4; ++$id) {
            /** @var ArticlePageDocument $child */
            $child = $this->prophesize(ArticlePageDocument::class);
            $child->getUuid()->willReturn($id);
            $child->getLocale()->willReturn($this->locale);
            $child->getShadowLocale()->willReturn(null);
            $child->isShadowLocaleEnabled()->willReturn(false);
            $child->getStructureType()->willReturn('nice_structure');
            $child->setShadowLocaleEnabled(true)->shouldBeCalled();
            $child->setShadowLocale('de')->shouldBeCalled();

            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);

            $this->documentManager->persist(
                $child,
                $this->locale,
                [
                    'clear_missing_content' => false,
                    'auto_name' => false,
                    'auto_rename' => false,
                ]
            )->shouldBeCalled();

            $children[] = $child;
        }

        $this->document->getStructureType()->willReturn('nice_structure');
        $this->document->getChildren()->willReturn($children);
        $this->document->getShadowLocale()->willReturn('de');
        $this->document->isShadowLocaleEnabled()->willReturn(true);

        $this->articleSubscriber->handleChildrenPersist(
            $this->prophesizeEvent(PersistEvent::class, $this->locale, [])
        );
    }

    public function testHydratePageData()
    {
        $node = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($node->reveal());

        $this->documentInspector->getLocalizationState(
            $this->document->reveal()
        )->willReturn(LocalizationState::LOCALIZED);

        $propertyName = 'i18n:' . $this->locale . '-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, $this->locale)
            ->willReturn($propertyName);

        $node->getPropertyValueWithDefault($propertyName, \json_encode([]))
            ->willReturn(\json_encode([['title' => 'Test title']]));

        $this->document->setPages([['title' => 'Test title']])->shouldBeCalled();
        $this->document->getOriginalLocale()->willReturn($this->locale);

        $this->articleSubscriber->hydratePageData($event->reveal());
    }

    public function testHydratePageDataShadow()
    {
        $node = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($node->reveal());

        $this->documentInspector->getLocalizationState(
            $this->document->reveal()
        )->willReturn(LocalizationState::SHADOW);

        $propertyNameEN = 'i18n:en-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, 'en')
            ->willReturn($propertyNameEN);

        $propertyNameDE = 'i18n:' . $this->locale . '-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, $this->locale)
            ->willReturn($propertyNameDE);

        $node->getPropertyValueWithDefault($propertyNameDE, \json_encode([]))
            ->willReturn(\json_encode(
                [
                    [
                        'title' => 'Test Überschrift',
                        'routePath' => '/test-ueberschrift',
                    ],
                ]
            ));

        $node->getPropertyValueWithDefault($propertyNameEN, \json_encode([]))
            ->willReturn(\json_encode(
                [
                    [
                        'title' => 'Test Headline',
                        'routePath' => '/test-headline',
                    ],
                ]
            ));

        $this->document->getLocale()->willReturn($this->locale);
        $this->document->getOriginalLocale()->willReturn('en');

        $this->document->setPages(
            [
                [
                    'title' => 'Test Überschrift',
                    'routePath' => '/test-headline',
                ],
            ]
        )->shouldBeCalled();

        $this->articleSubscriber->hydratePageData($event->reveal());
    }

    public function testPersistPageData()
    {
        $node = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn($this->locale);

        $pages = [
            [
                'uuid' => '123-123-123',
                'title' => 'Test article: page 1',
                'routePath' => '/test-article',
                'pageNumber' => 1,
            ],
            [
                'uuid' => '321-321-321',
                'title' => 'Test article: page 2',
                'routePath' => '/test-article/page-2',
                'pageNumber' => 2,
            ],
        ];

        $this->document->getUuid()->willReturn($pages[0]['uuid']);
        $this->document->getPageTitle()->willReturn($pages[0]['title']);
        $this->document->getRoutePath()->willReturn($pages[0]['routePath']);
        $this->document->getPageNumber()->willReturn($pages[0]['pageNumber']);

        $child = $this->prophesize(ArticlePageDocument::class);
        $child->getUuid()->willReturn($pages[1]['uuid']);
        $child->getPageTitle()->willReturn($pages[1]['title']);
        $child->getRoutePath()->willReturn($pages[1]['routePath']);
        $child->getPageNumber()->willReturn($pages[1]['pageNumber']);
        $this->document->getChildren()->willReturn(new \ArrayIterator([$child->reveal()]));

        $this->documentInspector->getLocalizationState($child->reveal())->willReturn(LocalizationState::LOCALIZED);

        $propertyName = 'i18n:' . $this->locale . '-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, $this->locale)
            ->willReturn($propertyName);

        $this->document->setPages($pages)->shouldBeCalled();
        $node->setProperty($propertyName, \json_encode($pages))->shouldBeCalled();

        $this->articleSubscriber->persistPageData($event->reveal());
    }

    public function testPersistPageDataOnReorder()
    {
        $node = $this->prophesize(NodeInterface::class);

        $orderedDocument = $this->prophesize(ArticlePageDocument::class);
        $orderedDocument->getParent()->willReturn($this->document->reveal());
        $this->document->getLocale()->willReturn($this->locale);
        $this->documentInspector->getNode($this->document->reveal())->willReturn($node->reveal());

        $event = $this->prophesize(ReorderEvent::class);
        $event->getDocument()->willReturn($orderedDocument->reveal());

        $pages = [
            [
                'uuid' => '123-123-123',
                'title' => 'Test article: page 1',
                'routePath' => '/test-article',
                'pageNumber' => 1,
            ],
            [
                'uuid' => '321-321-321',
                'title' => 'Test article: page 2',
                'routePath' => '/test-article/page-2',
                'pageNumber' => 2,
            ],
        ];

        $this->document->getUuid()->willReturn($pages[0]['uuid']);
        $this->document->getPageTitle()->willReturn($pages[0]['title']);
        $this->document->getRoutePath()->willReturn($pages[0]['routePath']);
        $this->document->getPageNumber()->willReturn($pages[0]['pageNumber']);

        $child = $this->prophesize(ArticlePageDocument::class);
        $child->getUuid()->willReturn($pages[1]['uuid']);
        $child->getPageTitle()->willReturn($pages[1]['title']);
        $child->getRoutePath()->willReturn($pages[1]['routePath']);
        $child->getPageNumber()->willReturn($pages[1]['pageNumber']);
        $this->document->getChildren()->willReturn(new \ArrayIterator([$child->reveal()]));

        $this->documentInspector->getLocalizationState($child->reveal())->willReturn(LocalizationState::LOCALIZED);
        $this->documentInspector->getLocale($this->document->reveal())->willReturn('de');

        $propertyName = 'i18n:' . $this->locale . '-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, $this->locale)->willReturn(
            $propertyName
        );

        $this->document->setPages($pages)->shouldBeCalled();
        $node->setProperty($propertyName, \json_encode($pages))->shouldBeCalled();
        $this->document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->document->setWorkflowStage(WorkflowStage::TEST);

        $this->documentManager->persist($this->document->reveal(), 'de')->shouldBeCalled();

        $this->articleSubscriber->persistPageDataOnReorder($event->reveal());
    }

    public function testHandleRemoveLocaleWithInvalidDocument()
    {
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $document = $this->prophesize(\stdClass::class); // Not an ArticleDocument
        $event->getDocument()->willReturn($document->reveal());

        // Ensure the indexer methods are not called
        $this->indexer->replaceWithGhostData()->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();

        // Call the method and make sure it doesn't throw exceptions
        $this->articleSubscriber->handleRemoveLocale($event->reveal());
    }

    public function testHandleRemoveLocale()
    {
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $document = $this->prophesize(ArticleDocument::class);
        $document->getOriginalLocale()->willReturn('en');
        $document->getUuid()->willReturn($this->uuid);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('de');

        $this->documentInspector->getConcreteLocales($document->reveal())->willReturn(['en', 'fr']);

        $originalDocument = $this->prophesize(ArticleDocument::class);
        $this->documentManager->find($this->uuid, 'en')->willReturn($originalDocument->reveal());

        // Ensure the indexer methods are called
        $this->indexer->replaceWithGhostData($originalDocument->reveal(), 'de')->shouldBeCalled();
        $this->indexer->flush()->shouldBeCalled();

        // Call the method
        $this->articleSubscriber->handleRemoveLocale($event->reveal());
    }

    public function testHandleRemoveLocaleWithDefaultLocaleDoesNotExists()
    {
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $document = $this->prophesize(ArticleDocument::class);
        $document->getOriginalLocale()->willReturn('en');
        $document->getUuid()->willReturn($this->uuid);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('de');

        $this->documentInspector->getConcreteLocales($document->reveal())->willReturn(['fr']);

        $originalDocument = $this->prophesize(ArticleDocument::class);
        $this->documentManager->find($this->uuid, 'fr')->willReturn($originalDocument->reveal());

        // Ensure the indexer methods are called
        $this->indexer->replaceWithGhostData($originalDocument->reveal(), 'de')->shouldBeCalled();
        $this->indexer->flush()->shouldBeCalled();

        // Call the method
        $this->articleSubscriber->handleRemoveLocale($event->reveal());
    }

    public function testHandleRemoveLocaleLiveWithInvalidDocument()
    {
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $document = $this->prophesize(\stdClass::class); // Not an ArticleDocument
        $event->getDocument()->willReturn($document->reveal());

        // Ensure the liveIndexer methods are not called
        $this->liveIndexer->remove()->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();

        // Call the method and make sure it doesn't throw exceptions
        $this->articleSubscriber->handleRemoveLocaleLive($event->reveal());
    }

    public function testHandleRemoveLocaleLive()
    {
        // Create a mock RemoveLocaleEvent with an ArticleDocument
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $document = $this->prophesize(ArticleDocument::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('en');

        // Ensure the liveIndexer methods are called
        $this->liveIndexer->remove($document->reveal(), 'en')->shouldBeCalled();
        $this->liveIndexer->flush()->shouldBeCalled();

        // Call the method
        $this->articleSubscriber->handleRemoveLocaleLive($event->reveal());
    }
}
