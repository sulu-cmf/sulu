<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Rest\ApiWrapper;

/**
 * Adds the hash of an object to its serialization, if it is auditable.
 */
class HashSerializeEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private HasherInterface $hasher)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'],
        ];
    }

    /**
     * Adds the hash of the given object to its serialization.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

        // FIXME This can be removed, as soon as we've got rid of all ApiEntities.
        if ($object instanceof ApiWrapper) {
            $object = $object->getEntity();
        }

        if (!$object instanceof AuditableInterface && !$object instanceof LocalizedAuditableBehavior) {
            return;
        }

        $visitor = $event->getVisitor();

        if (!$visitor instanceof SerializationVisitorInterface) {
            return;
        }

        $hash = $this->hasher->hash($object);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_hash', $hash),
            $hash
        );
    }
}
