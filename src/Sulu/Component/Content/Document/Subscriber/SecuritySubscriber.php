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

use PHPCR\PropertyInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles the security information on each node.
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    const SECURITY_PROPERTY_PREFIX = 'sec:role-';

    /**
     * @var array
     */
    private $permissions;

    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::HYDRATE => 'handleHydrate',
        ];
    }

    /**
     * Shows if the given document is supported by this subscriber.
     *
     * @param object $document
     *
     * @return bool
     */
    public function supports($document)
    {
        return $document instanceof SecurityBehavior;
    }

    /**
     * Adds the security information to the node.
     */
    public function handlePersist(PersistEvent $event)
    {
        /** @var SecurityBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document) || !$document->getPermissions()) {
            return;
        }

        $node = $event->getNode();

        $permissions = $document->getPermissions();

        $existingRoleIds = \array_keys($permissions);
        foreach ($node->getProperties(static::SECURITY_PROPERTY_PREFIX . '*') as $roleSecurityProperty) {
            $propertyRoleId = \str_replace(static::SECURITY_PROPERTY_PREFIX, '', $roleSecurityProperty->getName());
            if (\in_array(\intval($propertyRoleId), $existingRoleIds)) {
                continue;
            }

            $roleSecurityProperty->remove();
        }

        foreach ($permissions as $roleId => $permission) {
            // TODO use PropertyEncoder, once it is refactored
            $node->setProperty(static::SECURITY_PROPERTY_PREFIX . $roleId, $this->getAllowedPermissions($permission));
        }
    }

    /**
     * Adds the security information to the hydrated object.
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        $node = $event->getNode();

        if (!$this->supports($document)) {
            return;
        }

        $permissions = [];
        foreach ($node->getProperties('sec:*') as $property) {
            /** @var PropertyInterface $property */
            $roleId = \substr($property->getName(), 9); // remove the "sec:role-" prefix

            $allowedPermissions = $property->getValue();

            foreach ($this->permissions as $permission => $value) {
                $permissions[$roleId][$permission] = \in_array($permission, $allowedPermissions);
            }
        }

        $document->setPermissions($permissions);
    }

    /**
     * Extracts the keys of the allowed permissions into an own array.
     *
     * @param array $permissions
     *
     * @return array
     */
    private function getAllowedPermissions($permissions)
    {
        $allowedPermissions = [];
        foreach ($permissions as $permission => $allowed) {
            if ($allowed) {
                $allowedPermissions[] = $permission;
            }
        }

        return $allowedPermissions;
    }
}
