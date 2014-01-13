<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Listing;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

class ListRepository extends EntityRepository
{
    /**
     * @var ListRestHelper
     */
    private $helper;

    /**
     * @param ObjectManager $em
     * @param ClassMetadata $class
     * @param ListRestHelper $helper
     */
    public function __construct(ObjectManager $em, ClassMetadata $class, ListRestHelper $helper)
    {
        parent::__construct($em, $class);
        $this->helper = $helper;
    }

    /**
     * Find list with parameter
     *
     * @param array $where
     * @param string $prefix
     * @param bool $justCount Defines, if find should just return the total number of results
     * @return array|object|int
     */
    public function find($where = array(), $prefix = 'u', $justCount = false)
    {
        $searchPattern = $this->helper->getSearchPattern();
        $searchFields = $this->helper->getSearchFields();

        // if search string is set, but searchfields are not, take all fields into account
        if (!is_null($searchPattern) && (is_null($searchFields) || count($searchFields) == 0)) {
            $searchFields = $this->getEntityManager()->getClassMetadata($this->getEntityName())->getFieldNames();
        }

        $queryBuilder = new ListQueryBuilder(
            $this->getClassMetadata()->getAssociationNames(),
            $this->getEntityName(),
            $this->helper->getFields(),
            $this->helper->getSorting(),
            $where,
            $searchFields
        );

        if ($justCount) {
            $queryBuilder->justCount($prefix);
        }
        $dql = $queryBuilder->find($prefix);

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setFirstResult($this->helper->getOffset())
            ->setMaxResults($this->helper->getLimit());
        if ($searchPattern != null) {
            $query->setParameter('search', '%' . $searchPattern. '%');
        }

        // if just used for counting
        if ($justCount) {
            return intval($query->getSingleResult()['totalcount']);
        }

        return $query->getArrayResult();
    }

    /**
     * returns the amount of data
     * @param array $where
     * @param string $prefix
     * @return int
     */
    public function getCount($where = array(), $prefix = 'u')
    {
        return $this->find($where, $prefix, true);
    }
}
