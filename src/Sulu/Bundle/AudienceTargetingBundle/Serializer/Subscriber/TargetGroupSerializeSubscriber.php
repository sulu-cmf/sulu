<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;

/**
 * Extends target group serialization process.
 */
class TargetGroupSerializeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_DESERIALIZE,
                'format' => 'json',
                'method' => 'onPostDeserialize',
            ],
        ];
    }

    /**
     * Called after a target group was deserialized.
     */
    public function onPostDeserialize(ObjectEvent $event)
    {
        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $event->getObject();

        if (!$targetGroup instanceof TargetGroupInterface) {
            return;
        }

        /* @var TargetGroupWebspaceInterface $webspace */
        if ($targetGroup->getWebspaces()) {
            foreach ($targetGroup->getWebspaces() as $webspace) {
                $webspace->setTargetGroup($targetGroup);
            }
        }

        if ($targetGroup->getRules()) {
            foreach ($targetGroup->getRules() as $rule) {
                $rule->setTargetGroup($targetGroup);
            }
        }
    }
}
