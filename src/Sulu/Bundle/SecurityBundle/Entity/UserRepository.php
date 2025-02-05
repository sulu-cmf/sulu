<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Persistence\Repository\ORM\OrderByTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 *
 * @extends EntityRepository<UserInterface>
 */
class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    use OrderByTrait;

    public function findUsersByAccount($accountId, $sortBy = [])
    {
        try {
            $qb = $this->createQueryBuilder('user')
                ->leftJoin('user.userRoles', 'userRoles')
                ->leftJoin('userRoles.role', 'role')
                ->leftJoin('user.userSettings', 'settings')
                ->leftJoin('user.contact', 'contact')
                ->leftJoin('contact.emails', 'emails')
                ->leftJoin('contact.accountContacts', 'accountContacts')
                ->leftJoin('accountContacts.account', 'account')
                ->addSelect('userRoles')
                ->addSelect('role')
                ->addSelect('settings')
                ->addSelect('contact')
                ->addSelect('emails')
                ->where('account.id=:accountId');

            $this->addOrderBy($qb, 'user', $sortBy);

            $query = $qb->getQuery();
            $query->setParameter('accountId', $accountId);

            return $query->getResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    public function findUserById($id)
    {
        try {
            $qb = $this->createQueryBuilder('user')
                ->leftJoin('user.userRoles', 'userRoles')
                ->leftJoin('userRoles.role', 'role')
                ->leftJoin('user.userSettings', 'settings')
                ->leftJoin('user.contact', 'contact')
                ->leftJoin('contact.emails', 'emails')
                ->addSelect('userRoles')
                ->addSelect('role')
                ->addSelect('settings')
                ->addSelect('contact')
                ->addSelect('emails')
                ->where('user.id=:userId')
                ->setParameter('userId', $id);

            /** @var UserInterface */
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    public function findUsersById(array $ids)
    {
        $queryBuilder = $this->createQueryBuilder('user')
            ->leftJoin('user.contact', 'contact')
            ->addSelect('contact')
            ->where('user.id IN (:userIds)')
            ->setParameter('userIds', $ids);

        /** @var UserInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Searches for a user with a specific contact id.
     *
     * @return array
     */
    public function findUserByContact($id)
    {
        try {
            $qb = $this->createQueryBuilder('user')
                ->leftJoin('user.userRoles', 'userRoles')
                ->leftJoin('userRoles.role', 'role')
                ->leftJoin('user.userSettings', 'settings')
                ->leftJoin('user.contact', 'contact')
                ->leftJoin('contact.emails', 'emails')
                ->addSelect('userRoles')
                ->addSelect('role')
                ->addSelect('settings')
                ->addSelect('contact')
                ->addSelect('emails')
                ->where('user.contact=:contactId');

            $query = $qb->getQuery();
            $query->setParameter('contactId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Finds a user for the given username.
     *
     * This method throws UsernameNotFoundException if the user is not found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByUsername($username)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.username=:username');

        $query = $qb->getQuery();
        $query->setParameter('username', $username);

        return $query->getSingleResult();
    }

    /**
     * Finds all users for the role with the given id.
     *
     * @param int $roleId
     *
     * @return UserInterface[]
     */
    public function findAllUsersByRoleId($roleId)
    {
        $qb = $this->createQueryBuilder('user')
            ->leftJoin('user.userRoles', 'userRole')
            ->leftJoin('userRole.role', 'role')
            ->where('role=:roleId')
            ->setParameter('roleId', $roleId);

        /** @var UserInterface[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * Finds a user for a given email.
     *
     * @param string $email The email-address
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByEmail($email)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.email=:email');

        $query = $qb->getQuery();
        $query->setParameter('email', $email);

        return $query->getSingleResult();
    }

    /**
     * Finds a user for a given password-reset-token.
     *
     * @param string $token the reset-token
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByToken($token)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.passwordResetToken=:token');

        $query = $qb->getQuery();
        $query->setParameter('token', $token);

        return $query->getSingleResult();
    }

    public function findUserByIdentifier($identifier)
    {
        $qb = $this->getUserWithPermissionsQuery()
            ->where('user.email=:email')
            ->orWhere('user.username=:username');

        $query = $qb->getQuery();
        $query->setParameter('email', $identifier);
        $query->setParameter('username', $identifier);

        return $query->getSingleResult();
    }

    public function findUserWithSecurityById($id)
    {
        $queryBuilder = $this->getUserWithPermissionsQuery()
            ->where('user.id = :id');

        $query = $queryBuilder->getQuery();
        $query->setParameter('id', $id);

        return $query->getSingleResult();
    }

    /**
     * returns username for given apiKey.
     *
     * @param string $apiKey userId
     *
     * @return string
     */
    public function getUsernameByApiKey($apiKey)
    {
        $user = $this->findOneBy(['apiKey' => $apiKey]);
        if (!$user) {
            return;
        }

        return $user->getUserIdentifier();
    }

    /**
     * Returns all users with the given system including their contacts.
     *
     * @param string $system
     *
     * @return UserInterface[]
     */
    public function findUserBySystem($system)
    {
        $queryBuilder = $this->createQueryBuilder('user')
            ->select('user', 'contact')
            ->leftJoin('user.userRoles', 'userRoles')
            ->leftJoin('user.contact', 'contact')
            ->leftJoin('userRoles.role', 'role')
            ->where('role.system = :system')
            ->setParameter('system', $system);

        /** @var UserInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Returns the query for the user with the joins for retrieving the permissions. Especially useful for security
     * related queries.
     *
     * @return QueryBuilder
     */
    private function getUserWithPermissionsQuery()
    {
        return $this->createQueryBuilder('user')
            ->addSelect('userRoles')
            ->addSelect('role')
            ->addSelect('permissions')
            ->leftJoin('user.userRoles', 'userRoles')
            ->leftJoin('userRoles.role', 'role')
            ->leftJoin('role.permissions', 'permissions');
    }
}
