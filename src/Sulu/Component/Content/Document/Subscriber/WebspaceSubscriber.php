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

use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;

class WebspaceSubscriber extends AbstractMappingSubscriber
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    ) {
        parent::__construct($encoder);

        $this->inspector = $inspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // should happen after content is hydrated
            Events::HYDRATE => ['handleHydrate', -10],
        ];
    }

    public function supports($document)
    {
        return $document instanceof WebspaceBehavior;
    }

    /**
     * @param AbstractMappingEvent|HydrateEvent $event
     *
     * @throws \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $webspaceName = $this->inspector->getWebspace($document);
        $event->getAccessor()->set('webspaceName', $webspaceName);
    }

    public function doPersist(PersistEvent $event)
    {
    }
}
