<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;

/**
 * This class handles permission information for doctrine entities.
 */
class DoctrineAccessControlProvider implements AccessControlProviderInterface
{
    public function __construct(
        private ObjectManager $objectManager,
        private RoleRepositoryInterface $roleRepository,
        private AccessControlRepositoryInterface $accessControlRepository,
        private MaskConverterInterface $maskConverter,
    ) {
    }

    /**
     * Sets the permissions for the object with the given class and id for the given security identity.
     *
     * @param string $type The name of the class to protect
     * @param string $identifier
     * @param mixed[] $permissions
     */
    public function setPermissions($type, $identifier, $permissions)
    {
        $accessControls = $this->accessControlRepository->findByTypeAndId($type, $identifier);

        foreach ($permissions as $roleId => $rolePermissions) {
            $filteredAccessControl = \array_values(
                \array_filter($accessControls, function($accessControl) use ($roleId) {
                    return $accessControl->getRole()->getId() === $roleId;
                })
            );

            if (\count($filteredAccessControl) > 0) {
                $filteredAccessControl[0]->setPermissions(
                    $this->maskConverter->convertPermissionsToNumber($rolePermissions)
                );
            } else {
                /** @var RoleInterface|null $role */
                $role = $this->roleRepository->findRoleById($roleId);

                if (!$role) {
                    continue;
                }

                $accessControl = new AccessControl();
                $accessControl->setPermissions($this->maskConverter->convertPermissionsToNumber($rolePermissions));
                $accessControl->setRole($role);
                $accessControl->setEntityId($identifier);
                $accessControl->setEntityClass($type);
                $this->objectManager->persist($accessControl);
            }
        }

        $existingRoleIds = \array_keys($permissions);
        foreach ($accessControls as $accessControl) {
            if (\in_array($accessControl->getRole()->getId(), $existingRoleIds)) {
                continue;
            }

            $this->objectManager->remove($accessControl);
        }

        $this->objectManager->flush();
    }

    /**
     * Returns the permissions for all security identities.
     *
     * @param string $type The type of the protected object
     * @param string $identifier The identifier of the protected object
     *
     * @return array
     */
    public function getPermissions($type, $identifier, $system = null)
    {
        $accessControls = $this->accessControlRepository->findByTypeAndId($type, $identifier, $system);

        $permissions = [];
        foreach ($accessControls as $accessControl) {
            $permissions[$accessControl->getRole()->getId()] = $this->maskConverter->convertPermissionsToArray(
                $accessControl->getPermissions()
            );
        }

        return $permissions;
    }

    /**
     * Returns whether this provider supports the given type.
     *
     * @param string $type The name of the class protect
     *
     * @return bool
     */
    public function supports($type)
    {
        try {
            $class = new \ReflectionClass($type);
        } catch (\ReflectionException $e) {
            // in case the class does not exist there is no support
            return false;
        }

        return $class->implementsInterface(SecuredEntityInterface::class);
    }
}
