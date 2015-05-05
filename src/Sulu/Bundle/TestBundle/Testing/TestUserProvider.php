<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Entity\TestContact;
use Sulu\Bundle\TestBundle\Entity\TestUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * An UserProvider which returns always the same user for testing purposes
 * @package Sulu\Component\Testing
 */
class TestUserProvider implements UserProviderInterface
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function getUser()
    {
        if ($this->user) {
            return $this->user;
        }

        $user = $this->entityManager
            ->getRepository('Sulu\Bundle\SecurityBundle\Entity\User')
            ->findOneByUsername('test');

        if (!$user) {
            $contact = new Contact();
            $contact->setFirstName('Max');
            $contact->setLastName('Mustermann');
            $this->entityManager->persist($contact);

            $user = new User();
            $this->setCredentials($user);
            $user->setSalt('');
            $user->setLocale('en');
            $user->setContact($contact);
            $this->entityManager->persist($user);
        } else {
            $this->setCredentials($user);
        }

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
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByUsername($username)
    {
        return $this->getUser();
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->getUser();
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $class === 'Sulu\Bundle\CoreBundle\Entity\TestUser';
    }

    /**
     * Sets the standard credentials for the user
     */
    private function setCredentials(UserInterface $user)
    {
        $user->setUsername('test');
        $user->setPassword('test');
        $user->setSalt('');
    }
}
