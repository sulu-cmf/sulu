<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps the node name.
 */
class NodeNameSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'setFinalNodeName',
            Events::PERSIST => [
                ['setInitialNodeName', 0],
                ['setFinalNodeName', -480],
            ],
        ];
    }

    /**
     * Sets the initial node name.
     *
     * @param AbstractMappingEvent $event
     */
    public function setInitialNodeName(AbstractMappingEvent $event)
    {
        $this->setNodeName($event);
    }

    /**
     * Sets the final node name at the end, in case it was changed.
     *
     * @param AbstractMappingEvent $event
     */
    public function setFinalNodeName(AbstractMappingEvent $event)
    {
        $this->setNodeName($event);
    }

    /**
     * Sets the node name.
     *
     * @param AbstractMappingEvent $event
     *
     * @throws DocumentManagerException
     */
    private function setNodeName(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof NodeNameBehavior) {
            return;
        }

        $node = $event->getNode();
        $accessor = $event->getAccessor();
        $accessor->set(
            'nodeName',
            $node->getName()
        );
    }
}
