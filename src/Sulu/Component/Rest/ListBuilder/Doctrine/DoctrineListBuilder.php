<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\AbstractListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\AbstractDoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The listbuilder implementation for doctrine.
 */
class DoctrineListBuilder extends AbstractListBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The name of the entity to build the list for.
     *
     * @var string
     */
    private $entityName;

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $selectFields = [];

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $searchFields = [];

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $whereFields = [];

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $whereNotFields = [];

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $inFields = [];

    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;

    public function __construct(EntityManager $em, $entityName, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $subQueryBuilder = $this->createSubQueryBuilder('COUNT(' . $this->entityName . '.id)');

        $result = $subQueryBuilder->getQuery()->getScalarResult();
        $numResults = count($result);
        if ($numResults > 1) {
            return $numResults;
        } elseif ($numResults == 1) {
            $result = array_values($result[0]);

            return $result[0];
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        // emit listbuilder.create event
        $event = new ListBuilderCreateEvent($this);
        $this->eventDispatcher->dispatch(ListBuilderEvents::LISTBUILDER_CREATE, $event);

        // first create simplified id query
        // select ids with all necessary filter data
        $ids = $this->findIdsByGivenCriteria();

        // if no results are found - return
        if (count($ids) < 1) {
            return [];
        }

        // now select all data
        $this->queryBuilder = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);
        $this->assignJoins($this->queryBuilder);

        // Add all select fields
        foreach ($this->selectFields as $field) {
            $this->queryBuilder->addSelect($field->getSelect() . ' AS ' . $field->getName());
        }
        // group by
        $this->assignGroupBy($this->queryBuilder);
        // assign sort-fields
        $this->assignSortFields($this->queryBuilder);

        // use ids previously selected ids for query
        $this->queryBuilder->where($this->entityName . '.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $this->queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Function that finds all IDs of entities that match the
     * search criteria.
     *
     * @return array
     */
    protected function findIdsByGivenCriteria()
    {
        $subquerybuilder = $this->createSubQueryBuilder();
        if ($this->limit != null) {
            $subquerybuilder->setMaxResults($this->limit)->setFirstResult($this->limit * ($this->page - 1));
        }
        $this->assignSortFields($subquerybuilder);
        $ids = $subquerybuilder->getQuery()->getArrayResult();
        // if no results are found - return
        if (count($ids) < 1) {
            return [];
        }
        $ids = array_map(
            function ($array) {
                return $array['id'];
            },
            $ids
        );

        return $ids;
    }

    /**
     * Assigns ORDER BY clauses to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assignSortFields($queryBuilder)
    {
        foreach ($this->sortFields as $index => $sortField) {
            $queryBuilder->addOrderBy($sortField->getSelect(), $this->sortOrders[$index]);
        }
    }

    /**
     * Sets group by fields to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assignGroupBy($queryBuilder)
    {
        if (!empty($this->groupByFields)) {
            foreach ($this->groupByFields as $fields) {
                $queryBuilder->groupBy($fields->getSelect());
            }
        }
    }

    /**
     * Returns all the joins required for the query.
     *
     * @return DoctrineJoinDescriptor[]
     */
    protected function getJoins()
    {
        $joins = [];

        foreach ($this->sortFields as $sortField) {
            $joins = array_merge($joins, $sortField->getJoins());
        }

        foreach ($this->selectFields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        foreach ($this->searchFields as $searchField) {
            $joins = array_merge($joins, $searchField->getJoins());
        }

        foreach ($this->whereFields as $whereField) {
            $joins = array_merge($joins, $whereField->getJoins());
        }

        foreach ($this->inFields as $inField) {
            $joins = array_merge($joins, $inField->getJoins());
        }

        return $joins;
    }

    /**
     * Creates a query-builder for sub-selecting ID's.
     *
     * @param null|string $select
     *
     * @return QueryBuilder
     */
    protected function createSubQueryBuilder($select = null)
    {
        if (!$select) {
            $select = $this->entityName . '.id';
        }

        $filterFields = array_merge(
            $this->sortFields,
            $this->whereFields,
            $this->inFields,
            $this->betweenFields,
            $this->searchFields
        );

        // get entity names
        $filterFields = $this->getEntityNamesOfFieldDescriptors($filterFields);

        // use fields that have filter functionality or have an inner join
        $addJoins = [];
        foreach ($this->getJoins() as $entity => $join) {
            if (array_search($entity, $filterFields) !== false
                || $join->getJoinMethod() == DoctrineJoinDescriptor::JOIN_METHOD_INNER
            ) {
                $addJoins[$entity] = $join;
            }
        }

        $queryBuilder = $this->createQueryBuilder($addJoins)
            ->select($select);

        return $queryBuilder;
    }

    /**
     * Returns array of field-descriptor aliases.
     *
     * @param array $filterFields
     *
     * @return string[]
     */
    protected function getEntityNamesOfFieldDescriptors($filterFields)
    {
        $fields = [];

        // filter array for DoctrineFieldDescriptors
        foreach ($filterFields as $field) {
            // add joins of field
            $fields = array_merge($fields, $field->getJoins());

            if ($field instanceof DoctrineFieldDescriptor
                || $field instanceof DoctrineJoinDescriptor
            ) {
                $fields[] = $field;
            }
        }

        // get entity names
        $fields = array_map(
            function ($field) {
                return $field->getEntityName();
            },
            $fields
        );

        // unify result
        return array_unique($fields);
    }

    /**
     * Creates Querybuilder.
     *
     * @param array|null $joins Define which joins should be made
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder($joins = null)
    {
        $this->queryBuilder = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);

        $this->assignJoins($this->queryBuilder, $joins);

        // set where
        if (!empty($this->whereFields)) {
            $this->addWheres($this->whereFields, $this->whereValues, $this->whereComparators, $this->whereConjunctions);
        }

        // group by
        $this->assignGroupBy($this->queryBuilder);

        // set in
        if (!empty($this->inFields)) {
            $this->addIns($this->inFields, $this->inValues);
        }

        // set between
        if (!empty($this->betweenFields)) {
            $this->addBetweens($this->betweenFields, $this->betweenValues, $this->betweenConjunctions);
        }

        if ($this->search != null) {
            $searchParts = [];
            foreach ($this->searchFields as $searchField) {
                $searchParts[] = $searchField->getSelect() . ' LIKE :search';
            }

            $this->queryBuilder->andWhere('(' . implode(' OR ', $searchParts) . ')');
            $this->queryBuilder->setParameter('search', '%' . $this->search . '%');
        }

        return $this->queryBuilder;
    }

    /**
     * Adds joins to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $joins
     */
    protected function assignJoins(QueryBuilder $queryBuilder, array $joins = null)
    {
        if ($joins === null) {
            $joins = $this->getJoins();
        }

        foreach ($joins as $entity => $join) {
            switch ($join->getJoinMethod()) {
                case DoctrineJoinDescriptor::JOIN_METHOD_LEFT:
                    $queryBuilder->leftJoin(
                        $join->getJoin(),
                        $entity,
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
                case DoctrineJoinDescriptor::JOIN_METHOD_INNER:
                    $queryBuilder->innerJoin(
                        $join->getJoin(),
                        $entity,
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
            }
        }
    }

    /**
     * Adds where statements for in-clauses.
     *
     * @param array $inFields
     * @param array $inValues
     */
    protected function addIns(array $inFields, array $inValues)
    {
        $inParts = [];
        foreach ($inFields as $inField) {
            $inPart = $inField->getSelect() . ' IN (:' . $inField->getName() . ')';
            $this->queryBuilder->setParameter($inField->getName(), $inValues[$inField->getName()]);

            // null values
            if (array_search(null, $inValues[$inField->getName()])) {
                $inPart .= ' OR ' . $inField->getSelect() . ' IS NULL';
            }

            $inParts[] = $inPart;
        }

        $this->queryBuilder->andWhere('(' . implode(' AND ', $inParts) . ')');
    }

    /**
     * adds where statements for in-clauses.
     *
     * @param array $betweenFields
     * @param array $betweenValues
     * @param array $betweenConjunctions
     */
    protected function addBetweens(array $betweenFields, array $betweenValues, array $betweenConjunctions)
    {
        $betweenParts = [];
        $firstConjunction = null;

        foreach ($betweenFields as $betweenField) {
            $conjunction = ' ' . $betweenConjunctions[$betweenField->getName()] . ' ';

            if (!$firstConjunction) {
                $firstConjunction = $betweenConjunctions[$betweenField->getName()];
                $conjunction = '';
            }

            $betweenParts[] = $conjunction . $betweenField->getSelect() .
                ' BETWEEN :' . $betweenField->getName() . '1' .
                ' AND :' . $betweenField->getName() . '2';

            $values = $betweenValues[$betweenField->getName()];
            $this->queryBuilder->setParameter($betweenField->getName() . '1', $values[0]);
            $this->queryBuilder->setParameter($betweenField->getName() . '2', $values[1]);
        }

        $betweenString = implode('', $betweenParts);
        if (strtoupper($firstConjunction) === self::CONJUNCTION_OR) {
            $this->queryBuilder->orWhere('(' . $betweenString . ')');
        } else {
            $this->queryBuilder->andWhere('(' . $betweenString . ')');
        }
    }

    /**
     * Sets where statement.
     *
     * @param array $whereFields
     * @param array $whereValues
     * @param array $whereComparators
     * @param array $whereConjunctions
     */
    protected function addWheres(
        array $whereFields,
        array $whereValues,
        array $whereComparators,
        array $whereConjunctions
    ) {
        $whereParts = [];
        $firstConjunction = null;

        foreach ($whereFields as $whereField) {
            $conjunction = ' ' . $whereConjunctions[$whereField->getName()] . ' ';
            $value = $whereValues[$whereField->getName()];
            $comparator = $whereComparators[$whereField->getName()];

            if (!$firstConjunction) {
                $firstConjunction = $whereConjunctions[$whereField->getName()];
                $conjunction = '';
            }

            $whereParts[] = $this->createWherePart($value, $whereField, $conjunction, $comparator);
        }

        $whereString = implode('', $whereParts);
        if (strtoupper($firstConjunction) === self::CONJUNCTION_OR) {
            $this->queryBuilder->orWhere('(' . $whereString . ')');
        } else {
            $this->queryBuilder->andWhere('(' . $whereString . ')');
        }
    }

    /**
     * Creates a partial where statement.
     *
     * @param $value
     * @param $whereField
     * @param $conjunction
     * @param $comparator
     *
     * @return string
     */
    protected function createWherePart($value, $whereField, $conjunction, $comparator)
    {
        if ($value === null) {
            return $conjunction . $whereField->getSelect() . ' ' . $this->convertNullComparator($comparator);
        } elseif ($comparator === 'LIKE') {
            $this->queryBuilder->setParameter($whereField->getName(), '%' . $value . '%');
        } else {
            $this->queryBuilder->setParameter($whereField->getName(), $value);
        }

        return $conjunction . $whereField->getSelect() . ' ' . $comparator . ' :' . $whereField->getName();
    }

    /**
     * @param $comparator
     *
     * @return string
     */
    protected function convertNullComparator($comparator)
    {
        switch ($comparator) {
            case self::WHERE_COMPARATOR_EQUAL:
                return 'IS NULL';
            case self::WHERE_COMPARATOR_UNEQUAL:
                return 'IS NOT NULL';
            default:
                return $comparator;
        }
    }
}
