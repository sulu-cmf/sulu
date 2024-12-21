<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineBetweenExpression;

class DoctrineBetweenExpressionTest extends TestCase
{
    /**
     * @var string
     */
    private static $entityName = 'Sulu\Bundle\CoreBundle\Entity\Example';

    /**
     * http://php.net/manual/en/function.uniqid.php
     * With an empty prefix, the returned string will be 13 characters long. If more_entropy is TRUE,
     * it will be 23 characters.
     *
     * @var int
     */
    private $uniqueIdLength = 23;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function setUp(): void
    {
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder->expects($this->any())->method('setParameter')->willReturnSelf();
    }

    public function testGetStatement(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $start = 1;
        $end = 2;
        $whereExpression = new DoctrineBetweenExpression($fieldDescriptor, $start, $end);

        $statement = $whereExpression->getStatement($this->queryBuilder);
        $result = \preg_match(
            \sprintf(
                '/^Sulu_Bundle_CoreBundle_Entity_Example\.name BETWEEN :name[\S]{%1$s} AND :name[\S]{%1$s}/',
                $this->uniqueIdLength
            ),
            $statement
        );

        $this->assertEquals(1, $result);
    }
}
