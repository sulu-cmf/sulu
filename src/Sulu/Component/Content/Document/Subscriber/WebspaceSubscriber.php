<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebspaceSubscriber implements EventSubscriberInterface
{
    /**
     * @var PropertyEncoder
     */
    private $encoder;

    public function __construct(
        PropertyEncoder $encoder
    ) {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handleWebspace'],
            // should happen after content is hydrated
            Events::HYDRATE => ['handleWebspace', -10],
        ];
    }

    /**
     * @param AbstractMappingEvent $event
     */
    public function handleWebspace(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof WebspaceBehavior) {
            return;
        }

        $webspaceName = $event->getManager()->getInspector()->getWebspace($document);
        $event->getAccessor()->set('webspaceName', $webspaceName);
    }
}
