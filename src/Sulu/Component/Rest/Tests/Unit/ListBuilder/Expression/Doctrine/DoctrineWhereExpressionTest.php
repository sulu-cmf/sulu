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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineWhereExpression;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class DoctrineWhereExpressionTest extends TestCase
{
    use ProphecyTrait;

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
     * @var ObjectProphecy<QueryBuilder>
     */
    private $queryBuilder;

    public function setUp(): void
    {
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->queryBuilder->setParameter(Argument::any(), Argument::any())->willReturn($this->queryBuilder->reveal());
    }

    public function testGetStatement(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $value = 'test';
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value);

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder->reveal());
        $result = \preg_match(
            \sprintf('/^Sulu_Bundle_CoreBundle_Entity_Example\.name = :name[\S]{%s}/', $this->uniqueIdLength),
            $statement
        );
        $this->assertEquals(1, $result);
    }

    public static function nullProvider()
    {
        return [
            [ListBuilderInterface::WHERE_COMPARATOR_EQUAL, 'IS NULL'],
            [ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL, 'IS NOT NULL'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nullProvider')]
    public function testGetStatementNullValue($comparator, $expected): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, null, $comparator);

        $this->assertEquals(
            'Sulu_Bundle_CoreBundle_Entity_Example.name ' . $expected,
            $whereExpression->getStatement($this->queryBuilder->reveal())
        );
    }

    public function testGetStatementLike(): void
    {
        $value = 'test';
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value, 'LIKE');

        $this->queryBuilder->setParameter(Argument::containingString('name'), '%' . $value . '%');

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder->reveal());
        $result = \preg_match(
            \sprintf('/^Sulu_Bundle_CoreBundle_Entity_Example\.name LIKE :name[\S]{%s}/', $this->uniqueIdLength),
            $statement
        );
        $this->assertEquals(1, $result);
    }

    public static function andOrProvider()
    {
        return [
            ['and'],
            ['or'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('andOrProvider')]
    public function testGetStatementAndOr($comparator): void
    {
        $value = [1, 2, 3];
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value, $comparator);

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder->reveal());
        $result = \preg_match(
            \sprintf(
                '/^(Sulu_Bundle_CoreBundle_Entity_Example\.name = :name[\S]{%s}[\S]{1}( %s )?){3}/',
                $this->uniqueIdLength,
                $comparator
            ),
            $statement
        );
        $this->assertEquals(1, $result);
    }
}
