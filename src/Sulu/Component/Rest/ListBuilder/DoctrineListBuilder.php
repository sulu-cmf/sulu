<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Doctrine\ORM\EntityManager;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

class DoctrineListBuilder extends AbstractListBuilder
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The name of the entity to build the list for
     * @var string
     */
    private $entityName;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fields = array();

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $searchFields = array();

    /**
     * @var DoctrineFieldDescriptor
     */
    protected $sortField;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $whereFields = array();

    public function __construct(EntityManager $em, $entityName)
    {
        $this->em = $em;
        $this->entityName = $entityName;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $qb = $this->createQueryBuilder()
            ->select('count(' . $this->entityName . '.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $qb = $this->createQueryBuilder();

        foreach ($this->fields as $field) {
            $qb->addSelect($field->getFullName() . ' AS ' . $field->getAlias());
        }

        if ($this->limit != null) {
            $qb->setMaxResults($this->limit)->setFirstResult($this->limit * ($this->page - 1));
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Returns all the joins required for the query
     * @return array
     */
    private function getJoins()
    {
        $joins = array();

        if ($this->sortField != null) {
            $joins = array_merge($joins, $this->sortField->getJoins());
        }

        foreach ($this->fields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        foreach ($this->searchFields as $searchField) {
            $joins = array_merge($joins, $searchField->getJoins());
        }

        foreach ($this->whereFields as $whereField) {
            $joins = array_merge($joins, $whereField->getJoins());
        }

        return $joins;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createQueryBuilder()
    {
        $qb = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);

        foreach ($this->getJoins() as $entity => $join) {
            $qb->leftJoin($join, $entity);
        }

        if ($this->sortField != null) {
            $qb->orderBy($this->sortField->getFullName(), $this->sortOrder);
        }

        if (!empty($this->whereFields)) {
            $whereParts = array();
            foreach ($this->whereFields as $whereField) {
                $whereParts[] = $whereField->getFullName() . ' = :' . $whereField->getAlias();
                $qb->setParameter($whereField->getAlias(), $this->whereValues[$whereField->getAlias()]);
            }
            $qb->andWhere('(' . implode(' AND ', $whereParts) . ')');
        }

        if ($this->search != null) {
            $searchParts = array();
            foreach ($this->searchFields as $searchField) {
                $searchParts[] = $searchField->getFullName() . ' LIKE :search';
            }

            $qb->andWhere('(' . implode(' OR ', $searchParts) . ')');
            $qb->setParameter('search', '%' . $this->search . '%');
        }

        return $qb;
    }
}
