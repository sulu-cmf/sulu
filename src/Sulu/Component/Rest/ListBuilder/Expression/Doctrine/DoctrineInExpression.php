<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Expression\InExpressionInterface;

/**
 * Represents a IN expression for doctrine - needs a field and an array of values.
 */
class DoctrineInExpression extends AbstractDoctrineExpression implements InExpressionInterface
{
    public function __construct(protected DoctrineFieldDescriptorInterface $field, protected array $values)
    {
    }

    public function getStatement(QueryBuilder $queryBuilder)
    {
        $paramName = $this->getFieldName() . $this->getUniqueId();
        $values = $this->filterNullValues($this->getValues());
        $statement = '';

        if (\count($values) > 0) {
            $queryBuilder->setParameter($paramName, $values);
            $statement = $this->field->getSelect() . ' IN (:' . $paramName . ')';

            if (false !== \array_search(null, $this->getValues())) {
                $statement .= ' OR ' . $this->field->getSelect() . ' IS NULL';
            }
        } elseif (false !== \array_search(null, $this->getValues())) { // only null in values array
            $statement .= $this->field->getSelect() . ' IS NULL';
        }

        return $statement;
    }

    /**
     * Returns a new array without null values.
     *
     * @return array
     */
    protected function filterNullValues(array $values)
    {
        $result = \array_filter(
            $values,
            function($val) {
                return $val || 0 === $val || false === $val;
            }
        );

        return $result;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getFieldName()
    {
        return $this->field->getName();
    }
}
