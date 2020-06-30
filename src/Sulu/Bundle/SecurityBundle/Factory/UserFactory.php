<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Exception\RoleNotFoundException;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserFactory implements UserFactoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var SaltGenerator
     */
    private $saltGenerator;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepositoryInterface $userRepository,
        RoleRepositoryInterface $roleRepository,
        ContactRepositoryInterface $contactRepository,
        LocalizationManagerInterface $localizationManager,
        SaltGenerator $saltGenerator,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->contactRepository = $contactRepository;
        $this->localizationManager = $localizationManager;
        $this->saltGenerator = $saltGenerator;
        $this->encoderFactory = $encoderFactory;
    }

    public function create(
        string $username,
        string $firstName,
        string $lastName,
        string $email,
        string $locale,
        string $password,
        string $roleName = 'User'
    ): UserInterface {
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if ($user instanceof UserInterface) {
            return $user;
        }

        $locales = \array_keys($this->localizationManager->getLocalizations());

        return $this->createUser($firstName, $lastName, $email, $username, $password, $locale, $roleName, $locales);
    }

    private function generateSalt(): string
    {
        return $this->saltGenerator->getRandomSalt();
    }

    private function encodePassword(string $user, string $password, string $salt): string
    {
        return $this->encoderFactory->getEncoder($user)->encodePassword($password, $salt);
    }

    private function createUser(
        string $firstName,
        string $lastName,
        string $email,
        string $username,
        string $password,
        string $locale,
        string $roleName,
        array $locales
    ): UserInterface {
        $user = $this->userRepository->createNew();

        /** @var ContactInterface $contact */
        $contact = $this->contactRepository->createNew();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setMainEmail($email);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        $user->setContact($contact);
        $user->setUsername($username);
        $user->setSalt($this->generateSalt());
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $user->setLocale($locale);
        $user->setEmail($email);

        /** @var RoleInterface|null $role */
        $role = $this->roleRepository->findOneBy(['name' => $roleName]);

        if (null === $role) {
            throw new RoleNotFoundException($roleName);
        }

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale(\json_encode($locales)); // set all locales
        $this->entityManager->persist($userRole);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
