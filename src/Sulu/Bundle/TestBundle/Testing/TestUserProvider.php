<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * An UserProvider which returns always the same user for testing purposes.
 */
class TestUserProvider implements UserProviderInterface, ResetInterface
{
    public const TEST_USER_USERNAME = 'test';

    /**
     * @var UserInterface|null
     */
    private $user = null;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var PasswordHasherFactoryInterface|EncoderFactoryInterface
     */
    private $passwordHasherFactory;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @param PasswordHasherFactoryInterface|EncoderFactoryInterface $passwordHasherFactory
     */
    public function __construct(
        EntityManager $entityManager,
        ContactRepositoryInterface $contactRepository,
        UserRepositoryInterface $userRepository,
        $passwordHasherFactory,
        UserProviderInterface $userProvider
    ) {
        $this->entityManager = $entityManager;
        $this->contactRepository = $contactRepository;
        $this->userRepository = $userRepository;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->userProvider = $userProvider;
    }

    public function getUser()
    {
        if ($this->user) {
            return $this->user;
        }

        $user = $this->userRepository->findOneByUsername('test');

        if (!$user) {
            $contact = $this->contactRepository->createNew();
            $contact->setFirstName('Max');
            $contact->setLastName('Mustermann');
            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            $user = $this->userRepository->createNew();
            $user->setContact($contact);
            $this->entityManager->persist($user);
        }

        $user->setUsername(self::TEST_USER_USERNAME);
        $user->setEmail(self::TEST_USER_USERNAME . '@example.localhost');
        $user->setSalt('');

        if ($this->passwordHasherFactory instanceof PasswordHasherFactoryInterface) {
            $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
            $password = $hasher->hash('test');
        } else {
            $encoder = $this->passwordHasherFactory->getEncoder($user);
            $password = $encoder->encodePassword('test', $user->getSalt());
        }

        $user->setPassword($password);
        $user->setLocale('en');

        $this->entityManager->flush();

        $this->user = $user;

        return $this->user;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (self::TEST_USER_USERNAME === $identifier) {
            return $this->getUser();
        }

        return $this->userProvider->loadUserByIdentifier($identifier);
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (self::TEST_USER_USERNAME === $user->getUserIdentifier()) {
            return $this->getUser();
        }

        return $this->userProvider->refreshUser($user);
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     */
    public function supportsClass($class): bool
    {
        return $this->userProvider->supportsClass($class);
    }

    public function reset(): void
    {
        $this->user = null;
    }
}
