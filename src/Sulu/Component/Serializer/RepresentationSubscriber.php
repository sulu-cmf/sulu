<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\RepresentationInterface;

/**
 * @internal
 *
 * This handler workaround some problems with serialize Representation in specific groups
 */
class RepresentationSubscriber implements EventSubscriberInterface
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

    public function onPostSerialize(ObjectEvent $event)
    {
        $representation = $event->getObject();

        if (!$representation instanceof RepresentationInterface) {
            return;
        }

        $visitor = $event->getVisitor();

        $data = $representation->toArray();

        foreach ($data as $key => $value) {
            $visitor->visitProperty(new StaticPropertyMetadata(\get_class($representation), $key, $value), $value);
        }
    }
}
