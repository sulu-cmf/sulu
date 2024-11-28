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

use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;

/**
 * This class handles the permission information for PHPCR nodes.
 */
class PhpcrAccessControlProvider implements AccessControlProviderInterface
{
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private RoleRepositoryInterface $roleRepository,
        private array $permissions,
    ) {
    }

    public function setPermissions($type, $identifier, $permissions)
    {
        $document = $this->documentManager->find($identifier, null, ['rehydrate' => false]);
        $document->setPermissions($permissions);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    public function getPermissions($type, $identifier, $system = null)
    {
        if (!$identifier) {
            return [];
        }

        try {
            $document = $this->documentManager->find($identifier, null, ['rehydrate' => false]);
        } catch (DocumentNotFoundException $e) {
            return [];
        }

        if (!($document instanceof SecurityBehavior)) {
            return [];
        }

        $documentPermissions = $document->getPermissions();

        if (!$documentPermissions) {
            return [];
        }

        if (!$system) {
            return $documentPermissions;
        }

        $systemRoleIds = $this->roleRepository->findRoleIdsBySystem($system);

        return \array_filter(
            $documentPermissions,
            function($roleId) use ($systemRoleIds) {
                return \in_array($roleId, $systemRoleIds);
            },
            \ARRAY_FILTER_USE_KEY
        );
    }

    public function supports($type)
    {
        try {
            $class = new \ReflectionClass($type);
        } catch (\ReflectionException $e) {
            // in case the class does not exist there is no support
            return false;
        }

        return $class->implementsInterface(SecurityBehavior::class);
    }
}
