<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;

/**
 * This class describes a doctrine join.
 */
class DoctrineJoinDescriptor
{
    const JOIN_METHOD_LEFT = 'LEFT';

    const JOIN_METHOD_INNER = 'INNER';

    const JOIN_CONDITION_METHOD_ON = 'ON';

    const JOIN_CONDITION_METHOD_WITH = 'WITH';

    use EncodeAliasTrait;

    /**
     * The name of the entity to join.
     *
     * @var string
     */
    private $entityName;

    /**
     * The field, which should be joined.
     *
     * @var string
     */
    private $join;

    /**
     * The additional condition which should apply to the join.
     *
     * @var string
     */
    private $joinCondition;

    /**
     * The method for the condition to apply.
     *
     * @var string
     */
    private $joinConditionMethod;

    /**
     * Defines the join method (left, right or inner join).
     *
     * @var string
     */
    private $joinMethod;

    public function __construct(
        string $entityName,
        string $join,
        string $joinCondition = null,
        string $joinMethod = self::JOIN_METHOD_LEFT,
        string $joinConditionMethod = self::JOIN_CONDITION_METHOD_WITH
    ) {
        $this->entityName = $entityName;
        $this->join = $join;
        $this->joinCondition = $joinCondition;
        $this->joinConditionMethod = $joinConditionMethod;
        $this->joinMethod = $joinMethod;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getJoin()
    {
        return $this->encodeAlias($this->join);
    }

    /**
     * @return string
     */
    public function getJoinCondition()
    {
        return $this->encodeAlias($this->joinCondition);
    }

    /**
     * @return string
     */
    public function getJoinConditionMethod()
    {
        return $this->joinConditionMethod;
    }

    /**
     * @return string
     */
    public function getJoinMethod()
    {
        return $this->joinMethod;
    }
}
