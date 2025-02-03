<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Component\Content\Compat\Structure\PageBridge;

/**
 * Handle serialization and deserialization of the PageBridge.
 */
class PageBridgeSubscriber implements EventSubscriberInterface
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
        $bridge = $event->getObject();

        if (!$bridge instanceof PageBridge) {
            return;
        }

        $visitor = $event->getVisitor();

        $document = $bridge->getDocument();

        $data = [
            'document' => $document,
            'documentClass' => \get_class($document),
            'structure' => $bridge->getStructure()->getName(),
        ];

        foreach ($data as $key => $value) {
            $visitor->visitProperty(
                new StaticPropertyMetadata('', $key, $value),
                $value
            );
        }
    }
}
