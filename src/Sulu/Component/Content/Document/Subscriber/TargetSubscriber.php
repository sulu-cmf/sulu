<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\TargetBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Document which has another document as a target.
 */
class TargetSubscriber implements EventSubscriberInterface
{
    public const DOCUMENT_TARGET_FIELD = 'content';

    public static function getSubscribedEvents(): array
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(TargetBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping(
            'targetDocument',
            [
                'encoding' => 'system',
                'property' => self::DOCUMENT_TARGET_FIELD,
                'type' => 'reference',
            ]
        );
    }
}
