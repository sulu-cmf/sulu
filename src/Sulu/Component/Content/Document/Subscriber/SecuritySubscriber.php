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

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles the security information on each node.
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    /**
     * @deprecated use the SECURITY_PERMISSION_PROPERTY to access the permissions
     */
    public const SECURITY_PROPERTY_PREFIX = 'sec:role-';

    public const SECURITY_PERMISSION_PROPERTY = 'sec:permissions';

    public function __construct(
        private array $permissions,
        private SessionInterface $liveSession,
        private PropertyEncoder $propertyEncoder,
        private AccessControlManagerInterface $accessControlManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => [
                ['handlePersist', 0],
                ['handlePersistCreate', 3],
            ],
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

    public function handlePersistCreate(PersistEvent $event)
    {
        /** @var SecurityBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $node = $event->getNode();

        $isNewDocument = 0 === \count(
            $node->getProperties(
                $this->propertyEncoder->encode(
                    'system_localized',
                    StructureSubscriber::STRUCTURE_TYPE_FIELD,
                    '*'
                )
            )
        );

        if ($isNewDocument && $event->hasParentNode() && !$document->getPermissions()) {
            $parentNode = $event->getParentNode();
            $parentPermissions = $this->accessControlManager->getPermissions(
                SecurityBehavior::class,
                $parentNode->getIdentifier()
            );

            $document->setPermissions(
                $parentPermissions
            );
        }
    }

    /**
     * Adds the security information to the node.
     */
    public function handlePersist(PersistEvent $event)
    {
        /** @var SecurityBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document) || !\is_array($document->getPermissions())) {
            return;
        }

        $node = $event->getNode();
        $liveNode = $this->getLiveNode($document);

        $permissions = $document->getPermissions();

        $existingRoleIds = \array_keys($permissions);
        foreach ($node->getProperties(static::SECURITY_PROPERTY_PREFIX . '*') as $roleSecurityProperty) {
            $propertyName = $roleSecurityProperty->getName();
            $propertyRoleId = \str_replace(static::SECURITY_PROPERTY_PREFIX, '', $propertyName);
            if (\in_array(\intval($propertyRoleId), $existingRoleIds)) {
                continue;
            }

            $roleSecurityProperty->remove();
            if ($liveNode && $liveNode->hasProperty($propertyName)) {
                $liveNode->getProperty($propertyName)->remove();
            }
        }

        $allowedPermissions = [];
        foreach ($permissions as $roleId => $permission) {
            $allowedRolePermissions = $this->getAllowedPermissions($permission);
            $allowedPermissions[$roleId] = $allowedRolePermissions;
            // store role permissions in separated properties for backwards compatibility
            $node->setProperty(static::SECURITY_PROPERTY_PREFIX . $roleId, $allowedRolePermissions);
            if ($liveNode) {
                $liveNode->setProperty(static::SECURITY_PROPERTY_PREFIX . $roleId, $allowedRolePermissions);
            }
        }

        $jsonAllowedPermissions = \json_encode($allowedPermissions);

        $node->setProperty(static::SECURITY_PERMISSION_PROPERTY, $jsonAllowedPermissions);
        if ($liveNode) {
            $liveNode->setProperty(static::SECURITY_PERMISSION_PROPERTY, $jsonAllowedPermissions);
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

        if (!$node->hasProperty(static::SECURITY_PERMISSION_PROPERTY)) {
            return;
        }

        $nodePermissionJson = $node->getPropertyValue(static::SECURITY_PERMISSION_PROPERTY);

        if (!$nodePermissionJson) {
            return;
        }

        $nodePermissions = \json_decode($nodePermissionJson, true);

        $permissions = [];
        foreach ($nodePermissions as $roleId => $allowedPermissions) {
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

    /**
     * Returns the live node for given document.
     */
    private function getLiveNode(PathBehavior $document): ?NodeInterface
    {
        $path = $document->getPath();

        if (!$path) {
            return null;
        }

        try {
            return $this->liveSession->getNode($path);
        } catch (PathNotFoundException $e) {
            return null;
        }
    }
}
