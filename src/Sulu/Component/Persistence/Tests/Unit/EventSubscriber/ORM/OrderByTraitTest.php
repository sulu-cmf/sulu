<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Unit\EventSubscriber\ORM;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Persistence\Repository\ORM\OrderByTrait;

class OrderByTraitTest extends TestCase
{
    use ProphecyTrait;
    use OrderByTrait;

    public static function orderByProvider()
    {
        return [
            ['user', ['firstName' => 'ASC'], ['user.firstName' => 'ASC']],
            ['user', ['firstName' => 'ASC', 'test.a' => 'DESC'], ['user.firstName' => 'ASC', 'test.a' => 'DESC']],
            ['u', ['test.a' => 'DESC'], ['test.a' => 'DESC']],
            ['u', [], []],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('orderByProvider')]
    public function testAddOrderBy($alias, $orderBy, $expectedOrderBy): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        if (0 === \count($expectedOrderBy)) {
            $queryBuilder->addOrderBy(Argument::any(), Argument::any())->shouldNotBeCalled();
        }

        foreach ($expectedOrderBy as $field => $order) {
            $queryBuilder->addOrderBy($field, $order)->shouldBeCalledTimes(1);
        }

        $this->addOrderBy($queryBuilder->reveal(), $alias, $orderBy);
    }
}
