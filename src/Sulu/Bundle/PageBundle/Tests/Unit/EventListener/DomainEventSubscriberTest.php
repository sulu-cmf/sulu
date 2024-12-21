<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcher;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollector;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Domain\Event\PageRemovedEvent;
use Sulu\Bundle\PageBundle\EventListener\DomainEventSubscriber;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DomainEventSubscriberTest extends TestCase
{
    use SetGetPrivatePropertyTrait;

    public function testAddContextToPageRemovedEvent(): void
    {
        $pageDocument = new PageDocument();
        $pageDocument->setTitle('Cool page');
        $pageDocument->setLocale('en_US');
        $pageDocument->setResourceSegment('/cool-page');
        static::setPrivateProperty($pageDocument, 'uuid', '6860560d-5f90-445b-b263-7e88ce03db4d');
        static::setPrivateProperty($pageDocument, 'webspaceName', 'My Webspace');

        $eventDispatcher = new EventDispatcher();
        $domainEventSubscriber = new DomainEventSubscriber(
            $domainEventCollector = new DocumentDomainEventCollector(
                new DomainEventDispatcher($eventDispatcher)
            ),
            new DocumentManager($eventDispatcher),
            new PropertyEncoder(new NamespaceRegistry([]))
        );

        $domainEventSubscriber->handleRemove(new RemoveEvent($pageDocument));

        /** @var array<int, DomainEvent> $eventsToBeDispatched */
        $eventsToBeDispatched = static::getPrivateProperty($domainEventCollector, 'eventsToBeDispatched');
        $event = \current($eventsToBeDispatched);

        $this->assertCount(1, $eventsToBeDispatched);
        $this->assertInstanceOf(PageRemovedEvent::class, $event);
        $this->assertEquals(['url' => '/cool-page'], $event->getEventContext());
    }
}
