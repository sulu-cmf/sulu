<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\UserManager;

use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserCreatedEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserEnabledEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserLockedEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserModifiedEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserRemovedEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserUnlockedEvent;
use Sulu\Bundle\SecurityBundle\Entity\GroupRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

class UserManager implements UserManagerInterface
{
    use RelationTrait;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var GroupRepository
     */
    private $groupRepository;

    /**
     * @var ContactManager
     */
    protected $contactManager;

    /**
     * @var SaltGenerator
     */
    private $saltGenerator;

    /**
     * @var EncoderFactory|null
     */
    private $encoderFactory;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        ObjectManager $em,
        ?EncoderFactory $encoderFactory = null,
        RoleRepositoryInterface $roleRepository,
        GroupRepository $groupRepository,
        ContactManager $contactManager,
        SaltGenerator $saltGenerator,
        UserRepositoryInterface $userRepository,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->em = $em;
        $this->encoderFactory = $encoderFactory;
        $this->roleRepository = $roleRepository;
        $this->groupRepository = $groupRepository;
        $this->contactManager = $contactManager;
        $this->saltGenerator = $saltGenerator;
        $this->userRepository = $userRepository;
        $this->domainEventCollector = $domainEventCollector;
    }

    /**
     * Returns user for given id.
     *
     * @param int $id userId
     *
     * @return UserInterface|null
     */
    public function getUserById($id)
    {
        return $this->userRepository->find($id);
    }

    /**
     * Deletes a user with the given id.
     *
     * @return \Closure
     */
    public function delete()
    {
        $delete = function($id) {
            $user = $this->userRepository->findUserById($id);
            if (!$user) {
                throw new EntityNotFoundException($this->userRepository->getClassName(), $id);
            }

            $this->em->remove($user);
            $this->domainEventCollector->collect(new UserRemovedEvent($id, $user->getUsername()));

            foreach ($user->getRoleObjects() as $role) {
                $this->em->detach($role);

                foreach ($role->getUserRoles() as $userRole) {
                    $this->em->detach($userRole);
                }
            }

            $this->em->flush();
        };

        return $delete;
    }

    /**
     * Return all users.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->userRepository->findAll();
    }

    /**
     * Creates a new user with the given data.
     *
     * @param array $data
     * @param string $locale
     * @param null|int $id
     * @param bool $patch
     * @param bool $flush
     *
     * @return null|UserInterface
     *
     * @throws \Exception
     */
    public function save(
        $data,
        $locale,
        $id = null,
        $patch = false,
        $flush = true
    ) {
        $username = $this->getProperty($data, 'username');
        $contactId = $this->getProperty($data, 'contactId');
        $contact = $this->getProperty($data, 'contact');
        $email = $this->getProperty($data, 'email');
        $password = $this->getProperty($data, 'password');
        $enabled = $this->getProperty($data, 'enabled');
        $locked = $this->getProperty($data, 'locked');
        $user = null;

        $isNewUser = !$id;

        try {
            if (!$isNewUser) {
                // update user
                $user = $this->userRepository->findUserById($id);
                if (!$user) {
                    throw new EntityNotFoundException($this->userRepository->getClassName(), $id);
                }
                $this->processEmail($user, $email);
            } else {
                // add user
                if (!$this->isValidPassword($password)) {
                    throw new MissingPasswordException();
                }
                /** @var UserInterface $user */
                $user = $this->userRepository->createNew();
                $this->processEmail($user, $email, $contact);
            }

            // check if username is already in database and the current user is not the user with this username
            if (!$patch || null !== $username) {
                if ($username && 0 !== \strcasecmp($username, $user->getUsername()) && !$this->isUsernameUnique($username)) {
                    throw new UsernameNotUniqueException($username);
                }
                $user->setUsername($username);
            }

            // check if password is valid
            if (!$patch || null !== $password) {
                if ($this->isValidPassword($password)) {
                    $salt = $this->generateSalt();
                    $user->setSalt($salt);
                    $user->setPassword(
                        $this->encodePassword($user, $password, $salt)
                    );
                }
            }

            if (!$patch || null !== $this->getProperty($data, 'userRoles')) {
                if (!$this->processUserRoles($user, $this->getProperty($data, 'userRoles') ?: [])) {
                    throw new \Exception('Could not update dependencies!');
                }
            }

            if (!$patch || null !== $this->getProperty($data, 'userGroups')) {
                if (!$this->processUserGroups($user, $this->getProperty($data, 'userGroups') ?: [])) {
                    throw new \Exception('Could not update dependencies!');
                }
            }

            if (!$patch || (null !== $contact || null !== $contactId)) {
                if ($contact && !$contactId) {
                    @trigger_deprecation(
                        'sulu/sulu',
                        '1.4',
                        'Usage of the contact object to define the contact corresponding to the user is deprecated'
                        . ' since version 1.4 and will be removed in 2.0. Use the contactId query parameter instead.'
                    );
                }
                $user->setContact($this->getContact($contactId ?: $contact['id']));
            }

            if (!$patch || null !== $locale) {
                $user->setLocale($locale);
            }

            if (null !== $enabled) {
                $user->setEnabled($enabled);
            }

            if (null !== $locked) {
                $user->setLocked($locked);
            }
        } catch (\Exception $re) {
            if (isset($user)) {
                $this->em->remove($user);
            }

            throw $re;
        }

        $this->em->persist($user);
        if ($flush) {
            unset($data['password']);
            if ($isNewUser) {
                $this->domainEventCollector->collect(new UserCreatedEvent($user, $data));
            } else {
                $this->domainEventCollector->collect(new UserModifiedEvent($user, $data));
            }

            $this->em->flush();
        }

        return $user;
    }

    /**
     * Returns username for given id.
     *
     * @param int $id userId
     *
     * @return string
     */
    public function getUsernameByUserId($id)
    {
        return $this->getUserById($id)->getUsername();
    }

    /**
     * returns fullName for given id.
     *
     * @param int $id userId
     *
     * @return string
     */
    public function getFullNameByUserId($id)
    {
        $user = $this->getUserById($id);

        if (!$user) {
            return null;
        }

        return $user->getFullName();
    }

    /**
     * Checks if a username is unique
     * Null and empty will always return false.
     *
     * @param string $username
     *
     * @return bool
     */
    public function isUsernameUnique($username)
    {
        if ($username) {
            try {
                $this->userRepository->findUserByUsername($username);
            } catch (NoResultException $exc) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an email-adress is unique
     * Null and empty will always return false.
     *
     * @param string $email
     *
     * @return bool
     */
    public function isEmailUnique($email)
    {
        if ($email) {
            try {
                $this->userRepository->findUserByEmail($email);
            } catch (NoResultException $exc) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $id
     *
     * @return UserInterface
     */
    public function enableUser($id)
    {
        /** @var UserInterface $user */
        $user = $this->userRepository->findUserById($id);
        $user->setEnabled(true);
        $this->em->persist($user);

        $this->domainEventCollector->collect(new UserEnabledEvent($user));
        $this->em->flush();

        return $user;
    }

    /**
     * @param int $id
     *
     * @return UserInterface
     */
    public function lockUser($id)
    {
        /** @var UserInterface $user */
        $user = $this->userRepository->findUserById($id);
        $user->setLocked(true);
        $this->em->persist($user);

        $this->domainEventCollector->collect(new UserLockedEvent($user));
        $this->em->flush();

        return $user;
    }

    /**
     * @param int $id
     *
     * @return UserInterface
     */
    public function unlockUser($id)
    {
        /** @var UserInterface $user */
        $user = $this->userRepository->findUserById($id);
        $user->setLocked(false);
        $this->em->persist($user);

        $this->domainEventCollector->collect(new UserUnlockedEvent($user));
        $this->em->flush();

        return $user;
    }

    /**
     * Checks if the given password is a valid one.
     *
     * @param string $password The password to check
     *
     * @return bool True if the password is valid, otherwise false
     */
    public function isValidPassword($password)
    {
        return !empty($password);
    }

    /**
     * Process all user roles from request.
     *
     * @param array $userRoles
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processUserRoles(UserInterface $user, $userRoles)
    {
        $get = function($entity) {
            /* @var UserInterface $entity */
            return $entity->getId();
        };

        $delete = function($userRole) use ($user) {
            $user->removeUserRole($userRole);
            $this->em->remove($userRole);
        };

        $update = function($userRole, $userRoleData) {
            return $this->updateUserRole($userRole, $userRoleData);
        };

        $add = function($userRole) use ($user) {
            return $this->addUserRole($user, $userRole);
        };

        $entities = $user->getUserRoles();

        $result = $this->processSubEntities(
            $entities,
            $userRoles,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Process all user groups from request.
     *
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @param mixed $userGroups
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUserGroups(UserInterface $user, $userGroups)
    {
        $get = function($entity) {
            /* @var UserInterface $entity */
            return $entity->getId();
        };

        $delete = function($userGroup) use ($user) {
            $user->removeUserGroup($userGroup);
            $this->em->remove($userGroup);
        };

        $update = function($userGroup, $userGroupData) {
            return $this->updateUserGroup($userGroup, $userGroupData);
        };

        $add = function($userGroup) use ($user) {
            return $this->addUserGroup($user, $userGroup);
        };

        $entities = $user->getUserGroups();

        $result = $this->processSubEntities(
            $entities,
            $userGroups,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Updates an existing UserRole with the given data.
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function updateUserRole(UserRole $userRole, $userRoleData)
    {
        $role = $this->roleRepository->findRoleById($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException($this->roleRepository->getClassName(), $userRole['role']['id']);
        }

        $userRole->setRole($role);
        if (\array_key_exists('locales', $userRoleData)) {
            $userRole->setLocale(\json_encode($userRoleData['locales']));
        } else {
            $userRole->setLocale($userRoleData['locale']);
        }

        return true;
    }

    /**
     * Adds a new UserRole to the given user.
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addUserRole(UserInterface $user, $userRoleData)
    {
        $alreadyContains = false;

        $role = $this->roleRepository->findRoleById($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException($this->roleRepository->getClassName(), $userRoleData['role']['id']);
        }

        if ($user->getUserRoles()) {
            foreach ($user->getUserRoles() as $containedRole) {
                if ($containedRole->getRole()->getId() === $role->getId()) {
                    $alreadyContains = true;
                }
            }
        }

        if (false === $alreadyContains) {
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);
            $userRole->setLocale(\json_encode($userRoleData['locales']));
            $this->em->persist($userRole);

            $user->addUserRole($userRole);
        }

        return true;
    }

    /**
     * Adds a new UserGroup to the given user.
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addUserGroup(UserInterface $user, $userGroupData)
    {
        $group = $this->groupRepository->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException($this->groupRepository->getClassName(), $userGroupData['group']['id']);
        }

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);
        $userGroup->setLocale(\json_encode($userGroupData['locales']));
        $this->em->persist($userGroup);

        $user->addUserGroup($userGroup);

        return true;
    }

    /**
     * Updates an existing UserGroup with the given data.
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function updateUserGroup(UserGroup $userGroup, $userGroupData)
    {
        $group = $this->groupRepository->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException($this->groupRepository->getClassName(), $userGroup['group']['id']);
        }

        $userGroup->setGroup($group);
        if (\array_key_exists('locales', $userGroupData)) {
            $userGroup->setLocale(\json_encode($userGroupData['locales']));
        } else {
            $userGroup->setLocale($userGroupData['locale']);
        }

        return true;
    }

    /**
     * Returns the contact with the given id.
     *
     * @param int $id
     *
     * @return Contact
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function getContact($id)
    {
        $contact = $this->contactManager->findById($id);

        if (!$contact) {
            throw new EntityNotFoundException($this->contactManager->getContactEntityName(), $id);
        }

        return $contact;
    }

    /**
     * Generates a random salt for the password.
     *
     * @return string
     */
    private function generateSalt()
    {
        return $this->saltGenerator->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given passwort, with he given salt and returns the result.
     *
     * @param string $password
     * @param string $salt
     *
     * @return string
     */
    private function encodePassword(UserInterface $user, $password, $salt)
    {
        if (!$this->encoderFactory) {
            throw new \InvalidArgumentException(
                'For encoding a password the "EncoderFactory" must be passed to the "UserManager".'
            );
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * Return property for key or given default value.
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return string|null
     */
    private function getProperty($data, $key, $default = null)
    {
        if (\array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * Processes the email and adds it to the user.
     *
     * @param string $email
     * @param null|array $contact
     *
     * @throws EmailNotUniqueException
     */
    private function processEmail(UserInterface $user, $email, $contact = null)
    {
        if ($contact) {
            // if no email passed try to use the contact's first email
            if (null === $email
                && \array_key_exists('emails', $contact) && \count($contact['emails']) > 0
                && $this->isEmailUnique($contact['emails'][0]['email'])
            ) {
                $email = $contact['emails'][0]['email'];
            }
            if (null !== $email) {
                if (!$this->isEmailUnique($email)) {
                    throw new EmailNotUniqueException($email);
                }
                $user->setEmail($email);
            }
        } else {
            if ($email && 0 !== \strcasecmp($email, $user->getEmail()) && !$this->isEmailUnique($email)) {
                throw new EmailNotUniqueException($email);
            }
            $user->setEmail($email);
        }
    }

    /**
     * Finds all users for the given account.
     *
     * @param int $accountId
     *
     * @return array
     */
    public function findUsersByAccount($accountId, $sortBy = [])
    {
        return $this->userRepository->findUsersByAccount($accountId, $sortBy);
    }

    /**
     * Finds a user for a given contact id.
     *
     * @param int $contactId
     *
     * @return UserInterface
     */
    public function findUserByContact($contactId)
    {
        return $this->userRepository->findUserByContact($contactId);
    }

    /**
     * this is just a hack to avoid relations that start with index != 0
     * otherwise deserialization process will parse relations as object instead of an array
     * reindex entities.
     */
    private function resetIndexOfSubentites($entities)
    {
        if (\count($entities) > 0 && \method_exists($entities, 'getValues')) {
            $newEntities = $entities->getValues();
            $entities->clear();
            foreach ($newEntities as $value) {
                $entities->add($value);
            }
        }

        return $entities;
    }
}
