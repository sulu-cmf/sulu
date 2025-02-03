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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;

class TargetGroupRuleSerializeSubscriber implements EventSubscriberInterface
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
     * Called after a target group rule was deserialized.
     */
    public function onPostDeserialize(ObjectEvent $event)
    {
        /** @var TargetGroupRuleInterface $targetGroupRule */
        $targetGroupRule = $event->getObject();

        if (!$targetGroupRule instanceof TargetGroupRuleInterface) {
            return;
        }

        if ($targetGroupRule->getConditions()) {
            foreach ($targetGroupRule->getConditions() as $condition) {
                $condition->setRule($targetGroupRule);
            }
        }
    }
}
