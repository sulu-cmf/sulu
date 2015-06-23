<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 */
class SecurityTypeRepository extends EntityRepository
{
    /**
     * Searches for a role with a specific id.
     *
     * @param $id
     *
     * @return role
     */
    public function findSecurityTypeById($id)
    {
        try {
            $qb = $this->createQueryBuilder('securityType')
                ->where('securityType.id=:securityTypeId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('securityTypeId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }
}
