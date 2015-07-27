<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

/**
 * This interface defines the the ListBuilder functionality, for the creation of REST list responses.
 */
interface ListBuilderInterface
{
    const WHERE_COMPARATOR_EQUAL = '=';

    const WHERE_COMPARATOR_UNEQUAL = '!=';

    const WHERE_COMPARATOR_GREATER = '>';

    const WHERE_COMPARATOR_GREATER_THAN = '>=';

    const WHERE_COMPARATOR_LESS = '<';

    const WHERE_COMPARATOR_LESS_THAN = '<=';

    const SORTORDER_ASC = 'ASC';

    const SORTORDER_DESC = 'DESC';

    const CONJUNCTION_AND = 'AND';

    const CONJUNCTION_OR = 'OR';

    /**
     * Sets all the field descriptors for the ListBuilder at once.
     *
     * @param AbstractFieldDescriptor[] $fieldDescriptors
     *
     * @return mixed
     */
    public function setSelectFields($fieldDescriptors);

    /**
     * @deprecated use setSelectFields instead
     */
    public function setFields($fieldDescriptors);

    /**
     * Adds a field descriptor to the ListBuilder, which is then used to retrieve and return the list.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addSelectField(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * @deprecated use addSelectField instead
     */
    public function addField(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * Gets a field descriptor used by the ListBuilder to retrieve and return the list.
     *
     * @param string $fieldName
     *
     * @return AbstractFieldDescriptor
     */
    public function getSelectField($fieldName);

    /**
     * Checks if field by name has been already added.
     *
     * @param $name
     *
     * @return bool
     */
    public function hasSelectField($name);

    /**
     * @deprecated use hasSelectField instead
     */
    public function hasField($name);

    /**
     * Adds a field descriptor, which will be used for search.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addSearchField(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * Sets the search value for the search fields.
     *
     * @param string $search
     *
     * @return ListBuilderInterface
     */
    public function search($search);

    /**
     * Adds a field by which the table is sorted.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param string                  $order
     *
     * @return ListBuilderInterface
     */
    public function sort(AbstractFieldDescriptor $fieldDescriptor, $order = self::SORTORDER_ASC);

    /**
     * Defines how many items should be returned.
     *
     * @param int $limit
     *
     * @return ListBuilderInterface
     */
    public function limit($limit);

    /**
     * Returns the limit of the builder.
     *
     * @return int
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
     * Defines a constraint for the rows to return.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor The FieldDescriptor which is checked
     * @param string $value The value the FieldDescriptor should have
     * @param string $comparator The comparator use to compare the values
     * @param string $conjunction The conjunction to connect the where statements
     *
     * @return mixed
     */
    public function where(
        AbstractFieldDescriptor $fieldDescriptor,
        $value,
        $comparator = self::WHERE_COMPARATOR_EQUAL,
        $conjunction = self::CONJUNCTION_AND
    );

    /**
     * @deprecated use where instead
     */
    public function whereNot(AbstractFieldDescriptor $fieldDescriptor, $value);

    /**
     * Defines GROUP BY.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     *
     * @return mixed
     */
    public function addGroupBy(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * Defines an IN constraint.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param $values
     */
    public function in(AbstractFieldDescriptor $fieldDescriptor, $values);

    /**
     * Defines a between constraint.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param $values
     * @param string $conjunction The conjunction to connect the between statements
     *
     * @return
     */
    public function between(AbstractFieldDescriptor $fieldDescriptor, $values, $conjunction = self::CONJUNCTION_AND);

    /**
     * The number of total elements for this list.
     *
     * @return int
     */
    public function count();

    /**
     * Returns the objects for the built query.
     *
     * @return mixed
     */
    public function execute();

    /**
     * Sets an array of field descriptors.
     *
     * @param AbstractFieldDescriptor[] $fieldDescriptors
     */
    public function setFieldDescriptors(array $fieldDescriptors);

    /**
     * Returns a field descriptor by name.
     *
     * @param string $name
     *
     * @return AbstractFieldDescriptor | null
     */
    public function getFieldDescriptor($name);
}
