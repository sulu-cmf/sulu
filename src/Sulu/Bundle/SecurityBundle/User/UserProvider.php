<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\User;

use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Responsible for loading the user from the database for the Symfony security system. Takes also the security system
 * configuration from the webspaces into account.
 */
class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SystemStoreInterface $systemStore,
        private ManagerRegistry $doctrine,
    ) {
    }

    /**
     * @return UserInterface
     */
    public function loadUserByUsername($username)
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $exceptionMessage = \sprintf(
            'Unable to find an Sulu\Component\Security\Authentication\UserInterface object identified by %s',
            $identifier
        );

        try {
            $user = $this->userRepository->findUserByIdentifier($identifier);

            if (!$user->getEnabled()) {
                throw new DisabledException('User is not enabled yet.');
            }

            if ($user->getLocked()) {
                throw new LockedException('User is locked.');
            }

            $currentSystem = $this->systemStore->getSystem();

            foreach ($user->getRoleObjects() as $role) {
                if ($role->getSystem() === $currentSystem) {
                    return $user;
                }
            }
        } catch (NoResultException $e) {
            throw new UserNotFoundException($exceptionMessage, 0, $e);
        }

        throw new UserNotFoundException($exceptionMessage, 0);
    }

    public function refreshUser(BaseUserInterface $user)
    {
        $class = \get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                \sprintf(
                    'Instance of "%s" are not supported.',
                    $class
                )
            );
        }

        $user = $this->userRepository->findUserWithSecurityById($user->getId());

        if (!$user->getEnabled()) {
            throw new DisabledException('User is not enabled yet.');
        }

        if ($user->getLocked()) {
            throw new LockedException('User is locked.');
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return \is_subclass_of($class, UserInterface::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);

        $this->doctrine->getManager()->flush();
    }
}
