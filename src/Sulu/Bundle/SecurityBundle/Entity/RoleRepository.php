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
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 *
 * @extends EntityRepository<RoleInterface>
 */
class RoleRepository extends EntityRepository implements RoleRepositoryInterface
{
    public function findRoleById($id)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->addSelect('permissions')
                ->where('role.id=:roleId')
                ->setParameter('roleId', $id);

            /** @var RoleInterface */
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function findRoleByNameAndSystem($name, $system)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->addSelect('permissions')
                ->where('role.name=:roleName')
                ->andWhere('role.system=:roleSystem');

            $query = $queryBuilder->getQuery();
            $query->setParameter('roleName', $name);
            $query->setParameter('roleSystem', $system);

            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function findAllRoles(array $filters = [])
    {
        $queryBuilder = $this->createQueryBuilder('role')
            ->leftJoin('role.permissions', 'permissions')
            ->addSelect('permissions');

        if (isset($filters['anonymous'])) {
            $queryBuilder->andWhere('role.anonymous = :anonymous')
                ->setParameter('anonymous', $filters['anonymous']);
        }

        if (isset($filters['system'])) {
            $queryBuilder->andWhere('role.system = :roleSystem')
                ->setParameter('roleSystem', $filters['system']);
        }

        /** @var RoleInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    public function getRoleNames()
    {
        $query = $this->createQueryBuilder('role')
            ->select('role.name')
            ->getQuery();

        $roles = [];
        foreach ($query->getArrayResult() as $roleEntity) {
            $roles[] = $roleEntity['name'];
        }

        return $roles;
    }

    public function findRoleIdsBySystem($system)
    {
        /** @var array<array{id: int}> $result */
        $result = $this->createQueryBuilder('role')
            ->select('role.id')
            ->where('role.system = :roleSystem')
            ->setParameter('roleSystem', $system)
            ->getQuery()
            ->getResult();

        return \array_map(function($role) {
            return $role['id'];
        }, $result);
    }
}
