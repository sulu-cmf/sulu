<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;

/**
 * Adds information about the workflow stage to the serialized version of the document.
 */
class WorkflowStageSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Adds the published state to the serialization.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var RedirectTypeBehavior $document */
        $document = $event->getObject();

        if (!$document instanceof WorkflowStageBehavior) {
            return;
        }

        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        $published = WorkflowStage::PUBLISHED === $document->getWorkflowStage();
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'publishedState', $published),
            $published
        );
    }
}
