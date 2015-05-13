<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Compat\Content;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\Content\Document\ContentInstanceFactory;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * Listen to sulu node save event and index the document
 */
class ContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var SearchManagerInterface
     */
    protected $searchManager;

    /**
     * @var ContentInterface[]
     */
    private $documentsToDeindex = array();

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->searchManager = $searchManager;
        $this->metadataFactory = $metadataFactory;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => 'handlePersist',
            Events::REMOVE => array(
                array('handlePreRemove', 600),
            ),
        );
    }

    /**
     * Deindex/index document in search implementation depending
     * on the publish state
     *
     * @param ContentNodeEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ContentBehavior) {
            return;
        }

        $this->searchManager->index($document);
    }

    /**
     * Schedules a document to be deindexed
     *
     * @param ContentNodeDeleteEvent
     */
    public function handlePreRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ContentBehavior) {
            return;
        }

        $this->searchManager->deindex($document);
    }
}
