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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Sulu\Bundle\SecurityBundle\Entity\User;
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
use Webmozart\Assert\Assert;

/**
 * Responsible for loading the user from the database for the Symfony security system. Takes also the security system
 * configuration from the webspaces into account.
 *
 * @implements PasswordUpgraderInterface<User>
 */
class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SystemStoreInterface $systemStore,
        private ?EntityManagerInterface $entityManager,
    ) {
        if (null === $this->entityManager) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.5',
                \sprintf(
                    'The usage of the "%s" without setting "$entityManager" is deprecated and will not longer work in Sulu 3.0.',
                    self::class
                )
            );
        }
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

    public function refreshUser(BaseUserInterface $user): UserInterface
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

    public function supportsClass($class): bool
    {
        return \is_subclass_of($class, UserInterface::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) { // @phpstan-ignore-line we can not be 100% sure about this but generic forces us to check
            return;
        }

        $user->setPassword($newHashedPassword);

        Assert::notNull($this->entityManager, 'Entity manager is required for upgradePassword method.');

        $this->entityManager->flush();
    }
}
