<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Rest\ListBuilder\AbstractListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Sulu\Component\Rest\ListBuilder\Expression\BasicExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\AbstractDoctrineExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineAndExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineBetweenExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineInExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineNotExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineOrExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineWhereExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Exception\InvalidExpressionArgumentException;
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The listbuilder implementation for doctrine.
 */
class DoctrineListBuilder extends AbstractListBuilder
{
    use EncodeAliasTrait;

    /**
     * @var DoctrineFieldDescriptorInterface[]
     */
    protected $selectFields = [];

    /**
     * @var DoctrineFieldDescriptorInterface[]
     */
    protected $searchFields = [];

    /**
     * @var AbstractDoctrineExpression[]
     */
    protected $expressions = [];

    /**
     * Array of unique field descriptors from expressions.
     *
     * @var array
     */
    protected $expressionFields = [];

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var bool
     */
    private $distinct = false;

    /**
     * @var DoctrineFieldDescriptorInterface
     */
    private $idField;

    /**
     * @var bool
     */
    private $permissionCheckWithDynamicEntityClass = false;

    /**
     * @var class-string
     */
    private $securedEntityName;

    /**
     * @var string
     */
    private $securedEntityClassField;

    /**
     * @var string
     */
    private $securedEntityIdField;

    /**
     * Array of unique field descriptors needed for secure-check.
     *
     * @var array
     */
    private $permissionCheckFields = [];

    /**
     * @param class-string $entityName
     */
    public function __construct(
        private EntityManager $em,
        private $entityName,
        FilterTypeRegistry $filterTypeRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private array $permissions,
        private AccessControlQueryEnhancer $accessControlQueryEnhancer
    ) {
        parent::__construct($filterTypeRegistry);
        $this->idField = new DoctrineFieldDescriptor(
            'id',
            'id',
            $this->entityName,
            'public.id'
        );

        $this->securedEntityName = $this->entityName;
    }

    public function setSelectFields($fieldDescriptors)
    {
        parent::setSelectFields($fieldDescriptors);
        $this->selectFields = \array_filter(
            $this->selectFields,
            function(FieldDescriptorInterface $fieldDescriptor) {
                return $fieldDescriptor instanceof DoctrineFieldDescriptorInterface;
            }
        );
    }

    public function addSelectField(FieldDescriptorInterface $fieldDescriptor)
    {
        if ($fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            return parent::addSelectField($fieldDescriptor);
        }

        return $this;
    }

    /**
     * @param string $permission
     * @param string|null $securedEntityName
     *
     * @return self
     */
    public function setPermissionCheck(
        UserInterface $user,
        $permission,
        $securedEntityName = null
    ) {
        parent::setPermissionCheck($user, $permission);

        $this->permissionCheckWithDynamicEntityClass = false;
        $this->securedEntityName = $securedEntityName ?: $this->entityName;

        return $this;
    }

    public function setPermissionCheckWithDynamicEntityClass(
        UserInterface $user,
        string $permission,
        string $securedEntityClassField,
        string $securedEntityIdField
    ): self {
        parent::setPermissionCheck($user, $permission);

        $this->permissionCheckWithDynamicEntityClass = true;
        $this->securedEntityClassField = $securedEntityClassField;
        $this->securedEntityIdField = $securedEntityIdField;

        return $this;
    }

    public function addPermissionCheckField(DoctrineFieldDescriptor $fieldDescriptor)
    {
        $this->permissionCheckFields[$fieldDescriptor->getEntityName()] = $fieldDescriptor;
    }

    public function count()
    {
        $subQueryBuilder = $this->createSubQueryBuilder('COUNT( ' . $this->idField->getSelect() . ')', false);

        $this->assignParameters($subQueryBuilder);

        $result = $subQueryBuilder->getQuery()->getScalarResult();
        $numResults = \count($result);
        if ($numResults > 1) {
            return $numResults;
        } elseif (1 == $numResults) {
            $result = \array_values($result[0]);

            return (int) $result[0];
        }

        return 0;
    }

    public function execute()
    {
        parent::execute();

        // emit listbuilder.create event
        $event = new ListBuilderCreateEvent($this);
        $this->eventDispatcher->dispatch($event, ListBuilderEvents::LISTBUILDER_CREATE);
        $this->expressionFields = $this->getUniqueExpressionFieldDescriptors($this->expressions);

        // first create simplified id query
        // select ids with all necessary filter data
        $ids = $this->findIdsByGivenCriteria();

        // if no results are found - return
        if (\count($ids) < 1) {
            return [];
        }

        $this->queryBuilder = $this->createNonGroupQueryBuilder(
            $this->em->createQueryBuilder()->from($this->entityName, $this->encodeAlias($this->entityName))
        );

        // now select all data
        $this->assignJoins($this->queryBuilder, $this->getNonGroupJoins());

        // use ids previously selected ids for query
        $select = $this->idField->getSelect();
        $this->queryBuilder->where($select . ' IN (:ids)')->setParameter('ids', $ids);

        $sortFieldIsGrouped = \count(\array_filter($this->sortFields, fn (FieldDescriptorInterface $field) => $this->isGroupingFieldDescriptor($field))) > 0;
        // index the result to properly merge the grouped and non-grouped results and do not mess up sorting
        if ($sortFieldIsGrouped) {
            $this->queryBuilder->indexBy($this->encodeAlias($this->entityName), $this->idField->getSelect());
        }

        $this->assignParameters($this->queryBuilder);

        $nonGroupResult = $this->queryBuilder->getQuery()->getArrayResult();

        if (!$this->hasGroupingFieldDescriptor()) {
            return $nonGroupResult;
        }

        $this->queryBuilder = $this->createGroupQueryBuilder(
            $this->em->createQueryBuilder()->from($this->entityName, $this->encodeAlias($this->entityName))
        );

        // now select all data
        $this->assignJoins($this->queryBuilder, $this->getGroupJoins());

        // use ids previously selected ids for query
        $select = $this->idField->getSelect();
        $this->queryBuilder->where($select . ' IN (:ids)')->setParameter('ids', $ids);
        if (!$sortFieldIsGrouped) {
            $this->queryBuilder->indexBy($this->encodeAlias($this->entityName), $this->idField->getSelect());
        }

        $this->assignParameters($this->queryBuilder);

        $groupResult = $this->queryBuilder->getQuery()->getArrayResult();

        $result = [];
        $primaryResult = $sortFieldIsGrouped ? $groupResult : $nonGroupResult;
        $secondaryResult = $sortFieldIsGrouped ? $nonGroupResult : $groupResult;

        foreach ($primaryResult as $item) {
            $result[] = \array_merge($item, $secondaryResult[$item[$this->idField->getName()]] ?? []);
        }

        return $result;
    }

    protected function hasGroupingFieldDescriptor(): bool
    {
        foreach ($this->selectFields as $field) {
            if ($this->isGroupingFieldDescriptor($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return QueryBuilder
     */
    protected function createGroupQueryBuilder(QueryBuilder $queryBuilder)
    {
        $queryBuilder->addSelect($this->getSelectAs($this->idField));

        // Add all select fields
        foreach ($this->selectFields as $field) {
            if ($this->isGroupingFieldDescriptor($field)) {
                $queryBuilder->addSelect($this->getSelectAs($field));
            }
        }

        // group by
        $this->assignGroupBy($queryBuilder);

        $this->assignSortFields($queryBuilder);

        $queryBuilder->distinct($this->distinct);

        return $queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    protected function createNonGroupQueryBuilder(QueryBuilder $queryBuilder)
    {
        $hasId = false;

        // Add all select fields
        foreach ($this->selectFields as $field) {
            if (!$this->isGroupingFieldDescriptor($field)) {
                $queryBuilder->addSelect($this->getSelectAs($field));

                if ($field->getName() === $this->idField->getName()) {
                    $hasId = true;
                }
            }
        }

        if (!$hasId) {
            $queryBuilder->addSelect($this->getSelectAs($this->idField));
        }

        // assign sort-fields
        $this->assignSortFields($queryBuilder, false);

        $queryBuilder->distinct($this->distinct);

        return $queryBuilder;
    }

    /**
     * Function that finds all IDs of entities that match the
     * search criteria.
     *
     * @return array
     */
    protected function findIdsByGivenCriteria()
    {
        $subQueryBuilder = $this->createSubQueryBuilder($this->getSelectAs($this->idField));
        if (null != $this->limit) {
            $subQueryBuilder->setMaxResults((int) $this->limit)->setFirstResult((int) ($this->limit * ($this->page - 1)));
        }

        foreach ($this->sortFields as $sortField) {
            if ($sortField->getName() !== $this->idField->getName()) {
                $subQueryBuilder->addSelect($this->getSelectAs($sortField));

                if ($this->isGroupingFieldDescriptor($sortField)) {
                    $subQueryBuilder->addGroupBy($this->idField->getSelect());
                }
            }
        }

        $this->assignSortFields($subQueryBuilder);
        $this->assignParameters($this->queryBuilder);

        $ids = $subQueryBuilder->getQuery()->getArrayResult();

        // if no results are found - return
        if (\count($ids) < 1) {
            return [];
        }

        $ids = \array_map(
            function($array) {
                return $array[$this->idField->getName()];
            },
            $ids
        );

        return $ids;
    }

    private function assignParameters(QueryBuilder $queryBuilder)
    {
        $dql = $queryBuilder->getDQL();

        foreach ($this->parameters as $key => $parameter) {
            if (false !== \strpos($dql, ':' . $key)) {
                $queryBuilder->setParameter($key, $parameter);
            }
        }
    }

    /**
     * Assigns ORDER BY clauses to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assignSortFields($queryBuilder, bool $includeGroupBy = true)
    {
        // if no sort has been assigned add order by id ASC as default
        if (0 === \count($this->sortFields)) {
            $queryBuilder->addOrderBy($this->idField->getSelect(), 'ASC');
        }

        foreach ($this->sortFields as $index => $sortField) {
            $statement = $this->getSelectAs($sortField);
            if (!$includeGroupBy && $this->isGroupingFieldDescriptor($sortField)) {
                continue;
            }

            if (!$this->hasSelectStatement($queryBuilder, $statement)) {
                $queryBuilder->addSelect($this->getSelectAs($sortField, true));
            }

            $queryBuilder->addOrderBy($sortField->getName(), $this->sortOrders[$index]);
        }
    }

    protected function hasSelectStatement(QueryBuilder $queryBuilder, $statement)
    {
        foreach ($queryBuilder->getDQLPart('select') as $selectPart) {
            foreach ($selectPart->getParts() as $part) {
                if ($part === $statement) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sets group by fields to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assignGroupBy($queryBuilder)
    {
        $groupByFields = \array_merge($this->groupByFields, $this->sortFields);

        if (!empty($groupByFields)) {
            foreach ($groupByFields as $field) {
                if ($field instanceof DoctrineFieldDescriptor) {
                    $queryBuilder->addGroupBy($field->getSelect());
                }
            }
        }
    }

    /**
     * Returns all the joins required for the query.
     *
     * @return array<string, DoctrineJoinDescriptor>
     */
    protected function getJoins()
    {
        $joins = [];
        /** @var DoctrineFieldDescriptorInterface[] $fields */
        $fields = \array_merge($this->sortFields, $this->selectFields);

        foreach ($fields as $field) {
            $joins = \array_merge($joins, $field->getJoins());
        }

        return $joins;
    }

    protected function getGroupJoins()
    {
        $joins = [];

        foreach ($this->selectFields as $field) {
            if ($this->isGroupingFieldDescriptor($field)) {
                $joins = \array_merge($joins, $field->getJoins());
            }
        }

        /** @var DoctrineFieldDescriptorInterface $field */
        foreach ($this->sortFields as $field) {
            $joins = \array_merge($joins, $field->getJoins());
        }

        return $joins;
    }

    protected function getNonGroupJoins()
    {
        $joins = [];
        /** @var DoctrineFieldDescriptorInterface[] $fields */
        $fields = \array_merge($this->sortFields, $this->selectFields);

        foreach ($fields as $field) {
            if (!$this->isGroupingFieldDescriptor($field)) {
                $joins = \array_merge($joins, $field->getJoins());
            }
        }

        return $joins;
    }

    /**
     * Returns all DoctrineFieldDescriptors that were passed to list builder.
     *
     * @param bool $onlyReturnFilterFields Define if only filtering FieldDescriptors should be returned
     *
     * @return DoctrineFieldDescriptorInterface[]
     */
    protected function getAllFields(bool $onlyReturnFilterFields = false, bool $returnSortFields = true)
    {
        $fields = \array_merge(
            $this->searchFields,
            $this->getUniqueExpressionFieldDescriptors($this->expressions)
        );

        if ($returnSortFields) {
            $fields = \array_merge($fields, $this->sortFields);
        }

        if (true !== $onlyReturnFilterFields) {
            $fields = \array_merge($fields, $this->selectFields);
        }

        return \array_filter($fields, function(FieldDescriptorInterface $fieldDescriptor) {
            return $fieldDescriptor instanceof DoctrineFieldDescriptorInterface;
        });
    }

    /**
     * Creates a query-builder for sub-selecting ID's.
     *
     * @return QueryBuilder
     */
    protected function createSubQueryBuilder(string $select, bool $includeSortFields = true)
    {
        // get all filter-fields
        $filterFields = $this->getAllFields(true, $includeSortFields);

        // get entity names
        $entityNames = $this->getEntityNamesOfFieldDescriptors($filterFields);

        // get necessary joins to achieve filtering
        $addJoins = $this->getNecessaryJoins($entityNames);

        // create querybuilder and add select
        $queryBuilder = $this->createQueryBuilder($addJoins)->select($select);

        if ($this->user && $this->permission && \array_key_exists($this->permission, $this->permissions)) {
            if ($this->permissionCheckWithDynamicEntityClass) {
                $this->accessControlQueryEnhancer->enhanceWithDynamicEntityClass(
                    $queryBuilder,
                    $this->user,
                    $this->permissions[$this->permission],
                    $this->securedEntityName,
                    $this->encodeAlias($this->securedEntityName),
                    $this->securedEntityClassField,
                    $this->securedEntityIdField
                );
            } else {
                $this->accessControlQueryEnhancer->enhance(
                    $queryBuilder,
                    $this->user,
                    $this->permissions[$this->permission],
                    $this->securedEntityName,
                    $this->encodeAlias($this->securedEntityName)
                );
            }
        }

        return $queryBuilder;
    }

    /**
     * Function returns all necessary joins for filtering result.
     *
     * @param string[] $necessaryEntityNames
     *
     * @return DoctrineJoinDescriptor[]
     */
    protected function getNecessaryJoins($necessaryEntityNames)
    {
        $addJoins = [];

        // iterate through all field descriptors to find necessary joins
        foreach ($this->getAllFields() as $key => $field) {
            // if field is in any conditional clause -> add join
            if (($field instanceof DoctrineFieldDescriptor || $field instanceof DoctrineJoinDescriptor)
                && false !== \array_search($field->getEntityName(), $necessaryEntityNames)
                && $field->getEntityName() !== $this->entityName
            ) {
                $addJoins = \array_merge($addJoins, $field->getJoins());
            } else {
                // include inner joins
                foreach ($field->getJoins() as $entityName => $join) {
                    if (DoctrineJoinDescriptor::JOIN_METHOD_INNER !== $join->getJoinMethod()
                        && false === \array_search($entityName, $necessaryEntityNames)
                    ) {
                        break;
                    }
                    $addJoins = \array_merge($addJoins, [$entityName => $join]);
                }
            }
        }

        if ($this->user && $this->permission && \array_key_exists($this->permission, $this->permissions)) {
            foreach ($this->permissionCheckFields as $permissionCheckField) {
                $addJoins = \array_merge($addJoins, $permissionCheckField->getJoins());
            }
        }

        return $addJoins;
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
            $fields = \array_merge($fields, $field->getJoins());

            if ($field instanceof DoctrineFieldDescriptor
                || $field instanceof DoctrineJoinDescriptor
            ) {
                $fields[] = $field;
            }
        }

        $fieldEntityNames = [];
        foreach ($fields as $key => $field) {
            // special treatment for join descriptors
            if ($field instanceof DoctrineJoinDescriptor) {
                $fieldEntityNames[] = $key;
            }
            $fieldEntityNames[] = $field->getEntityName();
        }

        // unify result
        return \array_unique($fieldEntityNames);
    }

    /**
     * Creates Querybuilder.
     *
     * @param DoctrineJoinDescriptor[]|null $joins Define which joins should be made
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder($joins = null)
    {
        $this->queryBuilder = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->encodeAlias($this->entityName));

        $this->assignJoins($this->queryBuilder, $joins);

        if (null !== $this->ids) {
            $this->in($this->idField, !empty($this->ids) ? $this->ids : [null]);
        }

        if (null !== $this->excludedIds && !empty($this->excludedIds)) {
            $this->notIn($this->idField, $this->excludedIds);
        }

        // set expressions
        if (!empty($this->expressions)) {
            foreach ($this->expressions as $expression) {
                $this->queryBuilder->andWhere('(' . $expression->getStatement($this->queryBuilder) . ')');
            }
        }

        if (null !== $this->search) {
            $searchParts = [];
            foreach ($this->searchFields as $searchField) {
                $searchParts[] = $searchField->getSearch();
            }

            $this->queryBuilder->andWhere('(' . \implode(' OR ', $searchParts) . ')');
            $this->queryBuilder->setParameter('search', '%' . \str_replace('*', '%', $this->search) . '%');
        }

        return $this->queryBuilder;
    }

    /**
     * Adds joins to querybuilder.
     *
     * @param array<string, DoctrineJoinDescriptor>|null $joins
     */
    protected function assignJoins(QueryBuilder $queryBuilder, ?array $joins = null)
    {
        if (null === $joins) {
            $joins = $this->getJoins();
        }

        foreach ($joins as $entity => $join) {
            switch ($join->getJoinMethod()) {
                case DoctrineJoinDescriptor::JOIN_METHOD_LEFT:
                    $queryBuilder->leftJoin(
                        $join->getJoin() ?: $entity,
                        $this->encodeAlias($entity),
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
                case DoctrineJoinDescriptor::JOIN_METHOD_INNER:
                    $queryBuilder->innerJoin(
                        $join->getJoin() ?: $entity,
                        $this->encodeAlias($entity),
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
            }
        }
    }

    public function createNotExpression(ExpressionInterface $expression)
    {
        if (!$expression instanceof AbstractDoctrineExpression) {
            throw new InvalidExpressionArgumentException('not', 'expression');
        }

        return new DoctrineNotExpression($expression);
    }

    public function createWhereExpression(FieldDescriptorInterface $fieldDescriptor, $value, $comparator)
    {
        if (!$fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            throw new InvalidExpressionArgumentException('where', 'fieldDescriptor');
        }

        return new DoctrineWhereExpression($fieldDescriptor, $value, $comparator);
    }

    public function createInExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        if (!$fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            throw new InvalidExpressionArgumentException('in', 'fieldDescriptor');
        }

        return new DoctrineInExpression($fieldDescriptor, $values);
    }

    public function createBetweenExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        if (!$fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            throw new InvalidExpressionArgumentException('between', 'fieldDescriptor');
        }

        return new DoctrineBetweenExpression($fieldDescriptor, $values[0], $values[1]);
    }

    /**
     * Eliminates duplicated rows.
     *
     * @param bool $flag
     */
    public function distinct($flag = true)
    {
        $this->distinct = $flag;
    }

    /**
     * Set id-field of the "root" entity.
     */
    public function setIdField(DoctrineFieldDescriptorInterface $idField)
    {
        $this->idField = $idField;
    }

    /**
     * Returns an array of unique expression field descriptors.
     *
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @return array
     */
    protected function getUniqueExpressionFieldDescriptors(array $expressions)
    {
        if (0 === \count($this->expressionFields)) {
            $descriptors = [];
            $uniqueNames = \array_unique($this->getAllFieldNames($expressions));
            foreach ($uniqueNames as $uniqueName) {
                $descriptors[] = $this->fieldDescriptors[$uniqueName];
            }

            $this->expressionFields = $descriptors;

            return $descriptors;
        }

        return $this->expressionFields;
    }

    /**
     * Returns all fieldnames used in the expressions.
     *
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @return array
     */
    protected function getAllFieldNames($expressions)
    {
        $fieldNames = [];
        foreach ($expressions as $expression) {
            if ($expression instanceof ConjunctionExpressionInterface) {
                $fieldNames = \array_merge($fieldNames, $expression->getFieldNames());
            } elseif ($expression instanceof BasicExpressionInterface) {
                $fieldNames[] = $expression->getFieldName();
            }
        }

        return $fieldNames;
    }

    public function createAndExpression(array $expressions)
    {
        if (\count($expressions) >= 2) {
            return new DoctrineAndExpression($expressions);
        }

        throw new InvalidExpressionArgumentException('and', 'expressions');
    }

    public function createOrExpression(array $expressions)
    {
        if (\count($expressions) >= 2) {
            return new DoctrineOrExpression($expressions);
        }

        throw new InvalidExpressionArgumentException('or', 'expressions');
    }

    /**
     * Get select as from doctrine field descriptor.
     *
     * @param bool $hidden
     *
     * @return string
     */
    private function getSelectAs(DoctrineFieldDescriptorInterface $field, $hidden = false)
    {
        $select = $field->getSelect() . ' AS ';

        if ($hidden) {
            $select .= 'HIDDEN ';
        }

        return $select . $field->getName();
    }

    protected function isGroupingFieldDescriptor(FieldDescriptorInterface $field): bool
    {
        return $field instanceof DoctrineCountFieldDescriptor
            || $field instanceof DoctrineGroupConcatFieldDescriptor;
    }
}
