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
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Adds information about the shadow to the serialized document.
 */
class ShadowLocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentInspector $documentInspector,
        private DocumentRegistry $documentRegistry,
    ) {
    }

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
     * Adds the enabled shadow languages to the serialization.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!$document instanceof ShadowLocaleBehavior || !$this->documentRegistry->hasDocument($document)) {
            return;
        }

        $visitor = $event->getVisitor();

        $shadowLocales = $this->documentInspector->getShadowLocales($document);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'shadowLocales', $shadowLocales),
            $shadowLocales
        );
    }
}
