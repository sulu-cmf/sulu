<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class DocumentManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ObjectProphecy<NodeManager>
     */
    private $nodeManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var ObjectProphecy<Query>
     */
    private $query;

    /**
     * @var ObjectProphecy<QueryResultCollection>
     */
    private $queryResultCollection;

    public function setUp(): void
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentManager = new DocumentManager(
            $this->eventDispatcher,
            $this->nodeManager->reveal()
        );

        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new \stdClass();

        $this->query = $this->prophesize(Query::class);
        $this->queryResultCollection = $this->prophesize(QueryResultCollection::class);
    }

    /**
     * It should issue a persist event for the passed document.
     */
    public function testPersist(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->persist(new \stdClass(), 'fr');
        $this->assertTrue($subscriber->persist);
    }

    /**
     * It should issue a remove event.
     */
    public function testRemove(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->remove(new \stdClass());
        $this->assertTrue($subscriber->remove);
    }

    /**
     * It should issue a removeLocale event.
     */
    public function testRemoveLocale(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->removeLocale(new \stdClass(), 'en');
        $this->assertTrue($subscriber->removeLocale);
    }

    /**
     * It should issue a move event.
     */
    public function testMove(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->move(new \stdClass(), '/path/to');
        $this->assertTrue($subscriber->move);
    }

    /**
     * It should issue a copy event.
     */
    public function testCopy(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->copy(new \stdClass(), '/path/to');
        $this->assertTrue($subscriber->copy);
    }

    /**
     * It should issue a create event.
     */
    public function testCreate(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->create('foo');
        $this->assertTrue($subscriber->create);
    }

    public function testPublish(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->publish(new \stdClass(), 'de');
        $this->assertTrue($subscriber->publish);
    }

    public function testUnpublish(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->unpublish(new \stdClass(), 'de');
        $this->assertTrue($subscriber->unpublish);
    }

    public function testRemoveDraft(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->removeDraft(new \stdClass(), 'de');
        $this->assertTrue($subscriber->removeDraft);
    }

    public function testRestore(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->restore(new \stdClass(), 'de', '123-456-789');
        $this->assertTrue($subscriber->restore);
    }

    /**
     * It should issue a refresh event.
     */
    public function testRefresh(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->refresh($this->document);
        $this->assertTrue($subscriber->refresh);
    }

    /**
     * It should issue a clear event.
     */
    public function testClear(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->clear();
        $this->assertTrue($subscriber->clear);
    }

    /**
     * It should issue a flush event.
     */
    public function testFlush(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->flush();
        $this->assertTrue($subscriber->flush);
    }

    /**
     * It should issue a find event.
     */
    public function testFind(): void
    {
        $subscriber = $this->addSubscriber();
        $this->documentManager->find('foo', 'fr');
        $this->assertTrue($subscriber->find);
    }

    /**
     * It should throw an exception with invalid options.
     */
    public function testFindWithInvalidOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $subscriber = $this->addSubscriber();
        $this->documentManager->find('foo', 'bar', ['foo123' => 'bar']);
    }

    /**
     * It should pass options.
     */
    public function testFindWithOptions(): void
    {
        $subscriber = $this->addSubscriber();
        $this->assertNotNull($this->documentManager->find('foo', 'bar', ['test.foo' => 'bar']));
    }

    /**
     * It should issue a query create event.
     */
    public function testQueryCreate(): void
    {
        $subscriber = $this->addSubscriber();
        $query = $this->documentManager->createQuery('SELECT foo FROM [foo:bar]', 'fr');
        $this->assertTrue($subscriber->queryCreate);
        $this->assertInstanceOf(Query::class, $query);
    }

    /**
     * It should issue a query builder create event.
     *
     * NOT SUPPORTED
     */
    public function testQueryCreateBuilder(): void
    {
        $this->markTestSkipped('Not supported yet');
    }

    private function addSubscriber()
    {
        $subscriber = new TestDocumentManagerSubscriber($this->query->reveal(), $this->queryResultCollection->reveal());
        $this->eventDispatcher->addSubscriber($subscriber);

        return $subscriber;
    }
}

class TestDocumentManagerSubscriber implements EventSubscriberInterface
{
    public $persist = false;

    public $hydrate = false;

    public $remove = false;

    public $removeLocale = false;

    public $copy = false;

    public $move = false;

    public $create = false;

    public $clear = false;

    public $flush = false;

    public $find = false;

    public $queryCreate = false;

    public $queryCreateBuilder = false;

    public $queryExecute = false;

    public $publish = false;

    public $unpublish = false;

    public $removeDraft = false;

    public $restore = false;

    public $refresh = false;

    public $reorder = false;

    public function __construct(private Query $query, private QueryResultCollection $resultCollection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::REMOVE => 'handleRemove',
            Events::REMOVE_LOCALE => 'handleRemoveLocale',
            Events::MOVE => 'handleMove',
            Events::COPY => 'handleCopy',
            Events::CREATE => 'handleCreate',
            Events::CLEAR => 'handleClear',
            Events::FLUSH => 'handleFlush',
            Events::FIND => 'handleFind',
            Events::QUERY_CREATE => 'handleQueryCreate',
            Events::QUERY_CREATE_BUILDER => 'handleQueryBuilderCreate',
            Events::QUERY_EXECUTE => 'handleQueryExecute',
            Events::REFRESH => 'handleRefresh',
            Events::REORDER => 'handleReorder',
            Events::CONFIGURE_OPTIONS => 'handleConfigureOptions',
            Events::PUBLISH => 'handlePublish',
            Events::UNPUBLISH => 'handleUnpublish',
            Events::REMOVE_DRAFT => 'handleRemoveDraft',
            Events::RESTORE => 'handleRestore',
        ];
    }

    public function handleConfigureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults([
            'test.foo' => 'bar',
        ]);
    }

    public function handlePersist(PersistEvent $event)
    {
        $this->persist = true;
    }

    public function handleRemove(RemoveEvent $event)
    {
        $this->remove = true;
    }

    public function handleRemoveLocale(RemoveLocaleEvent $event)
    {
        $this->removeLocale = true;
    }

    public function handleCopy(CopyEvent $event)
    {
        $this->copy = true;
    }

    public function handleMove(MoveEvent $event)
    {
        $this->move = true;
    }

    public function handleCreate(CreateEvent $event)
    {
        $this->create = true;
        $event->setDocument(new \stdClass());
    }

    public function handleClear(ClearEvent $event)
    {
        $this->clear = true;
    }

    public function handleFlush(FlushEvent $event)
    {
        $this->flush = true;
    }

    public function handleFind(FindEvent $event)
    {
        $this->find = true;
        $event->setDocument(new \stdClass());
    }

    public function handleQueryCreate(QueryCreateEvent $event)
    {
        $this->queryCreate = true;
        $event->setQuery($this->query);
    }

    public function handleQueryExecute(QueryExecuteEvent $event)
    {
        $this->queryExecute = true;
        $event->setResult($this->resultCollection);
    }

    public function handlePublish(PublishEvent $event)
    {
        $this->publish = true;
    }

    public function handleUnpublish(UnpublishEvent $event)
    {
        $this->unpublish = true;
    }

    public function handleRemoveDraft(RemoveDraftEvent $event)
    {
        $this->removeDraft = true;
    }

    public function handleRefresh(RefreshEvent $event)
    {
        $this->refresh = true;
    }

    public function handleReorder(ReorderEvent $event)
    {
        $this->reorder = true;
    }

    public function handleRestore(RestoreEvent $event)
    {
        $this->restore = true;
    }
}
