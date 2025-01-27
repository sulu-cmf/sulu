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

use Sulu\Component\Rest\Exception\InvalidSearchException;
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Sulu\Component\Rest\ListBuilder\Metadata\AbstractPropertyMetadata;
use Sulu\Component\Security\Authentication\UserInterface;

abstract class AbstractListBuilder implements ListBuilderInterface
{
    /**
     * The field descriptors for the current list.
     *
     * @var FieldDescriptorInterface[]
     */
    protected $selectFields = [];

    /**
     * The field descriptors for the field, which will be used for the search.
     *
     * @var FieldDescriptorInterface[]
     */
    protected $searchFields = [];

    /**
     * The value for which the searchfields will be searched.
     *
     * @var string
     */
    protected $search;

    /**
     * The field descriptor for the field to sort.
     *
     * @var FieldDescriptorInterface[]
     */
    protected $sortFields = [];

    /**
     * Defines the sort order of the string.
     *
     * @var string[]
     */
    protected $sortOrders;

    /**
     * The limit for this query.
     *
     * @var int|null
     */
    protected $limit = null;

    /**
     * Array of ids to which the result of the query is restricted.
     * If null, the result is not restricted to specific ids.
     *
     * @var array|null
     */
    protected $ids = null;

    /**
     * Array of ids which are excluded from the result of the query.
     * If null, no ids will be excluded from the result.
     *
     * @var array|null
     */
    protected $excludedIds = null;

    /**
     * group by fields.
     *
     * @var array
     */
    protected $groupByFields = [];

    /**
     * The page the resulting query will be returning.
     *
     * @var int
     */
    protected $page = 1;

    /**
     * All field descriptors for the current context.
     *
     * @var FieldDescriptorInterface[]
     */
    protected $fieldDescriptors = [];

    /**
     * @var ExpressionInterface[]
     */
    protected $expressions = [];

    /**
     * @var mixed[]
     */
    protected $parameters = [];

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var string
     */
    protected $permission;

    public function __construct(private FilterTypeRegistry $filterTypeRegistry)
    {
    }

    public function setSelectFields($fieldDescriptors)
    {
        $this->selectFields = \array_filter(
            $fieldDescriptors,
            function(FieldDescriptorInterface $fieldDescriptor) {
                if (null === $fieldDescriptor->getMetadata()) {
                    return true;
                }

                /** @var AbstractPropertyMetadata $propertyMetadata */
                $propertyMetadata = $fieldDescriptor->getMetadata();

                return FieldDescriptorInterface::VISIBILITY_NEVER !== $propertyMetadata->getVisibility();
            }
        );
    }

    public function addSelectField(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->selectFields[$fieldDescriptor->getName()] = $fieldDescriptor;

        return $this;
    }

    public function getSelectField($fieldName)
    {
        if (\array_key_exists($fieldName, $this->selectFields)) {
            return $this->selectFields[$fieldName];
        }

        return;
    }

    public function hasSelectField($name)
    {
        return \array_key_exists($name, $this->selectFields);
    }

    public function setFieldDescriptors(array $fieldDescriptors)
    {
        $this->fieldDescriptors = $fieldDescriptors;
    }

    public function getFieldDescriptor($fieldName)
    {
        if (\array_key_exists($fieldName, $this->fieldDescriptors)) {
            return $this->fieldDescriptors[$fieldName];
        }

        return;
    }

    public function addSearchField(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->searchFields[] = $fieldDescriptor;

        return $this;
    }

    public function search($search)
    {
        $this->search = $search;

        return $this;
    }

    public function filter($filter)
    {
        foreach ($filter as $fieldName => $options) {
            $fieldDescriptor = $this->fieldDescriptors[$fieldName];
            $this->filterTypeRegistry->getFilterType($fieldDescriptor->getMetadata()->getFilterType())
                ->filter($this, $fieldDescriptor, $options);
        }

        return $this;
    }

    public function sort(FieldDescriptorInterface $fieldDescriptor, $order = self::SORTORDER_ASC)
    {
        $existingIndex = $this->retrieveIndexOfFieldDescriptor($fieldDescriptor, $this->sortFields);

        if (false !== $existingIndex) {
            $this->sortOrders[$existingIndex] = $order;
        } else {
            // Else add to list of sort-fields.
            $this->sortFields[] = $fieldDescriptor;
            $this->sortOrders[] = $order;
        }

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setCurrentPage($page)
    {
        $this->page = $page;

        return $this;
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    public function setIds($ids)
    {
        $this->ids = $ids;

        return $this;
    }

    public function getIds()
    {
        return $this->ids;
    }

    public function setExcludedIds($excludedIds)
    {
        $this->excludedIds = $excludedIds;

        return $this;
    }

    public function getExcludedIds()
    {
        return $this->excludedIds;
    }

    public function setPermissionCheck(UserInterface $user, $permission)
    {
        $this->user = $user;
        $this->permission = $permission;

        return $this;
    }

    public function where(FieldDescriptorInterface $fieldDescriptor, $value, $comparator = self::WHERE_COMPARATOR_EQUAL)
    {
        $this->expressions[] = $this->createWhereExpression($fieldDescriptor, $value, $comparator);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    public function in(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        $this->expressions[] = $this->createInExpression($fieldDescriptor, $values);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    public function notIn(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        $this->expressions[] = $this->createNotExpression($this->createInExpression($fieldDescriptor, $values));
        $this->addFieldDescriptor($fieldDescriptor);
    }

    public function between(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        $this->expressions[] = $this->createBetweenExpression($fieldDescriptor, $values);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    public function execute()
    {
        if (null !== $this->search && \count($this->searchFields) <= 0) {
            throw new InvalidSearchException('Searching is not possible, because no search fields have been defined');
        }
    }

    /**
     * Adds a field descriptor.
     */
    protected function addFieldDescriptor(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->fieldDescriptors[$fieldDescriptor->getName()] = $fieldDescriptor;
    }

    public function addGroupBy(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->groupByFields[$fieldDescriptor->getName()] = $fieldDescriptor;
    }

    public function addExpression(ExpressionInterface $expression)
    {
        $this->expressions[] = $expression;
    }

    public function setParameter(string $key, $parameter)
    {
        $this->parameters[$key] = $parameter;
    }

    /**
     * Returns index of given FieldDescriptor in given array of descriptors.
     * If no match is found, false will be returned.
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     *
     * @return bool|int|string
     */
    protected function retrieveIndexOfFieldDescriptor(
        FieldDescriptorInterface $fieldDescriptor,
        array $fieldDescriptors
    ) {
        foreach ($fieldDescriptors as $index => $other) {
            if ($fieldDescriptor->compare($other)) {
                return $index;
            }
        }

        return false;
    }
}
