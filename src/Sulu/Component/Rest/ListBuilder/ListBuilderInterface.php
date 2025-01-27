<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Sulu\Component\Rest\ListBuilder\Expression\BetweenExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\InExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\WhereExpressionInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * This interface defines the the ListBuilder functionality, for the creation of REST list responses.
 */
interface ListBuilderInterface
{
    public const WHERE_COMPARATOR_EQUAL = '=';

    public const WHERE_COMPARATOR_UNEQUAL = '!=';

    public const WHERE_COMPARATOR_GREATER = '>';

    public const WHERE_COMPARATOR_GREATER_THAN = '>=';

    public const WHERE_COMPARATOR_LESS = '<';

    public const WHERE_COMPARATOR_LESS_THAN = '<=';

    public const SORTORDER_ASC = 'ASC';

    public const SORTORDER_DESC = 'DESC';

    public const CONJUNCTION_AND = 'AND';

    public const CONJUNCTION_OR = 'OR';

    /**
     * Sets all the field descriptors for the ListBuilder at once.
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     */
    public function setSelectFields($fieldDescriptors);

    /**
     * Adds a field descriptor to the ListBuilder, which is then used to retrieve and return the list.
     *
     * @return ListBuilderInterface
     */
    public function addSelectField(FieldDescriptorInterface $fieldDescriptor);

    /**
     * Gets a field descriptor used by the ListBuilder to retrieve and return the list.
     *
     * @param string $fieldName
     *
     * @return FieldDescriptorInterface
     */
    public function getSelectField($fieldName);

    /**
     * Checks if field by name has been already added.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasSelectField($name);

    /**
     * Adds a field descriptor, which will be used for search.
     *
     * @return ListBuilderInterface
     */
    public function addSearchField(FieldDescriptorInterface $fieldDescriptor);

    /**
     * Sets the search value for the search fields.
     *
     * @param string $search
     *
     * @return ListBuilderInterface
     */
    public function search($search);

    /**
     * @param array $filter
     *
     * @return ListBuilderInterface
     */
    public function filter($filter);

    /**
     * Adds a field by which the table is sorted.
     *
     * @param string $order
     *
     * @return ListBuilderInterface
     */
    public function sort(FieldDescriptorInterface $fieldDescriptor, $order = self::SORTORDER_ASC);

    /**
     * Defines how many items should be returned.
     *
     * @param int|null $limit
     *
     * @return ListBuilderInterface
     */
    public function limit($limit);

    /**
     * Returns the limit of the builder.
     *
     * @return int|null
     */
    public function getLimit();

    /**
     * Sets the current page for the builder.
     *
     * @param int $page
     *
     * @return ListBuilderInterface
     */
    public function setCurrentPage($page);

    /**
     * Returns the current page.
     *
     * @return int
     */
    public function getCurrentPage();

    /**
     * Restricts the rows to return to have one of the given ids.
     * If null, the rows to return are not restricted to specific ids.
     *
     * @param array|null $ids
     *
     * @return ListBuilderInterface
     */
    public function setIds($ids);

    /**
     * Returns an array of ids to which the rows to return are restricted.
     * If null, the rows to return are not restricted to specific ids.
     *
     * @return array|null
     */
    public function getIds();

    /**
     * Excludes the given ids from the rows to return.
     * If null, no ids will be excluded from the rows to return.
     *
     * @param array|null $excludedIds
     *
     * @return ListBuilderInterface
     */
    public function setExcludedIds($excludedIds);

    /**
     * Returns an array of ids which are excluded from the rows to return.
     * If null, no ids will be excluded from the rows to return.
     *
     * @return array|null
     */
    public function getExcludedIds();

    /**
     * Sets the permission check for the ListBuilder.
     *
     * @param UserInterface $user The user for which the permission must be granted
     * @param string $permission A value from the PermissionTypes
     *
     * @return ListBuilderInterface
     */
    public function setPermissionCheck(UserInterface $user, $permission);

    /**
     * Defines a constraint for the rows to return.
     *
     * @param FieldDescriptorInterface $fieldDescriptor The FieldDescriptor which is checked
     * @param ?string $value The value the FieldDescriptor should have
     * @param string $comparator The comparator use to compare the values
     *
     * @return ListBuilderInterface
     */
    public function where(
        FieldDescriptorInterface $fieldDescriptor,
        $value,
        $comparator = self::WHERE_COMPARATOR_EQUAL
    );

    /**
     * Defines GROUP BY.
     *
     * @return ListBuilderInterface
     */
    public function addGroupBy(FieldDescriptorInterface $fieldDescriptor);

    /**
     * Defines an IN constraint.
     */
    public function in(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Defines an NOT IN constraint.
     */
    public function notIn(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Defines a between constraint.
     *
     * @param int[] $values
     */
    public function between(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * The number of total elements for this list.
     *
     * @return int
     */
    public function count();

    /**
     * Returns the objects for the built query.
     */
    public function execute();

    /**
     * Sets an array of field descriptors.
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     */
    public function setFieldDescriptors(array $fieldDescriptors);

    /**
     * Returns a field descriptor by name.
     *
     * @param string $name
     *
     * @return FieldDescriptorInterface|null
     */
    public function getFieldDescriptor($name);

    /**
     * Adds an expression.
     */
    public function addExpression(ExpressionInterface $expression);

    /**
     * Creates a between expression from the given values.
     *
     * @return BetweenExpressionInterface
     */
    public function createBetweenExpression(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Creates an in expression from the given values.
     *
     * @return InExpressionInterface
     */
    public function createInExpression(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Creates an where expression from the given values.
     *
     * @param string $comparator
     *
     * @return WhereExpressionInterface
     */
    public function createWhereExpression(FieldDescriptorInterface $fieldDescriptor, $value, $comparator);

    /**
     * Creates a negation of the given expression.
     *
     * @return ExpressionInterface
     */
    public function createNotExpression(ExpressionInterface $createInExpression);

    /**
     * Creates an and expression with the given expressions.
     *
     * @param ExpressionInterface[] $expressions
     *
     * @return ConjunctionExpressionInterface
     */
    public function createAndExpression(array $expressions);

    /**
     * Creates an or expressions with the given expressions.
     *
     * @param ExpressionInterface[] $expressions
     *
     * @return ConjunctionExpressionInterface
     */
    public function createOrExpression(array $expressions);
}
