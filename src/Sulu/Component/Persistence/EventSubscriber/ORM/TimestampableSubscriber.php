<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Sulu\Component\Persistence\Model\TimestampableInterface;

/**
 * Manage the timestamp fields on models implementing the
 * TimestampableInterface.
 */
class TimestampableSubscriber
{
    public const CREATED_FIELD = 'created';

    public const CHANGED_FIELD = 'changed';

    /**
     * Load the class data, mapping the created and changed fields
     * to datetime fields.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        if (null !== $reflection && $reflection->implementsInterface(TimestampableInterface::class)) {
            if (!$metadata->hasField(self::CREATED_FIELD)) {
                $metadata->mapField([
                    'fieldName' => self::CREATED_FIELD,
                    'type' => 'datetime',
                    'nullable' => false,
                ]);
            }

            if (!$metadata->hasField(self::CHANGED_FIELD)) {
                $metadata->mapField([
                    'fieldName' => self::CHANGED_FIELD,
                    'type' => 'datetime',
                    'nullable' => false,
                ]);
            }
        }
    }

    /**
     * Set the timestamps before update.
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->handleTimestamp($event);
    }

    /**
     * Set the timestamps before creation.
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $this->handleTimestamp($event);
    }

    /**
     * Set the timestamps. If created is NULL then set it. Always
     * set the changed field.
     */
    private function handleTimestamp(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $meta = $event->getObjectManager()->getClassMetadata(\get_class($entity));

        $created = $meta->getFieldValue($entity, self::CREATED_FIELD);
        if (null === $created) {
            $meta->setFieldValue($entity, self::CREATED_FIELD, new \DateTime());
        }

        $meta->setFieldValue($entity, self::CHANGED_FIELD, new \DateTime());
    }
}
