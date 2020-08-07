<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\System;

use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;

class SystemStore implements SystemStoreInterface
{
    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var string
     */
    private $system;

    /**
     * @var ?RoleInterface
     */
    private $anonymousRole;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getSystem(): ?string
    {
        return $this->system;
    }

    public function setSystem(string $system): void
    {
        $this->system = $system;
        $this->anonymousRole = null;
    }

    public function getAnonymousRole(): ?RoleInterface
    {
        if (!$this->anonymousRole) {
            $anonymousRoles = $this->roleRepository->findAllRoles(['anonymous' => true, 'system' => $this->system]);

            $this->anonymousRole = $anonymousRoles[0] ?? null;
        }

        return $this->anonymousRole;
    }
}
