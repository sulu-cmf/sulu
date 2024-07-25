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
use Sulu\Component\Rest\ListBuilder\Expression\BasicExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\Exception\InsufficientExpressionsException;

/**
 * This class is used as base class for the conjunctions expressions AND and OR.
 */
class DoctrineConjunctionExpression extends AbstractDoctrineExpression implements ConjunctionExpressionInterface
{
    /**
     * DoctrineAndExpression constructor.
     *
     * @param string $conjunction
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @throws InsufficientExpressionsException
     */
    public function __construct(protected $conjunction, private array $expressions)
    {
        if (\count($this->expressions) < 2) {
            throw new InsufficientExpressionsException($this->expressions);
        }
    }

    public function getStatement(QueryBuilder $queryBuilder)
    {
        $statements = [];
        foreach ($this->expressions as $expression) {
            $statements[] = $expression->getStatement($queryBuilder);
        }

        return \implode(' ' . $this->conjunction . ' ', $statements);
    }

    public function getExpressions()
    {
        return $this->expressions;
    }

    public function getFieldNames()
    {
        $result = [];
        foreach ($this->expressions as $expression) {
            if ($expression instanceof ConjunctionExpressionInterface) {
                $result = \array_merge($result, $expression->getFieldNames());
            } elseif ($expression instanceof BasicExpressionInterface) {
                $result[] = $expression->getFieldName();
            }
        }

        return $result;
    }
}
