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

use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RedirectTypeSubscriber implements EventSubscriberInterface
{
    public const REDIRECT_TYPE_FIELD = 'nodeType';

    public const INTERNAL_FIELD = 'internal_link';

    public const EXTERNAL_FIELD = 'external';

    public static function getSubscribedEvents(): array
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
            // has to be called sooner, because the ResourceSegmentSubscriber relies ont that value
            Events::PERSIST => ['handlePersist', 15],
        ];
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RedirectTypeBehavior) {
            return;
        }

        if ($document->getRedirectTarget() === $document) {
            throw new \InvalidArgumentException('You are not allowed to link a page to itself!');
        }
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(RedirectTypeBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping(
            'redirectType',
            [
                'encoding' => 'system_localized',
                'property' => self::REDIRECT_TYPE_FIELD,
                'default' => RedirectType::NONE,
            ]
        );
        $metadata->addFieldMapping(
            'redirectExternal',
            [
                'encoding' => 'system_localized',
                'property' => self::EXTERNAL_FIELD,
            ]
        );
        $metadata->addFieldMapping(
            'redirectTarget',
            [
                'encoding' => 'system_localized',
                'property' => self::INTERNAL_FIELD,
                'type' => 'reference',
            ]
        );
    }
}
