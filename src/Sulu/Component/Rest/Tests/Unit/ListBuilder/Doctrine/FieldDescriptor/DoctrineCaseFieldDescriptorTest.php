<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine\FieldDescriptor;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;

class DoctrineCaseFieldDescriptorTest extends TestCase
{
    public static function dataProvider()
    {
        return [
            [
                'test',
                new DoctrineDescriptor('entity1', 'field1'),
                new DoctrineDescriptor('entity2', 'field2'),
                '(CASE WHEN entity1.field1 IS NOT NULL THEN entity1.field1 ELSE entity2.field2 END)',
                [],
                'entity1.field1 LIKE :search OR (entity1.field1 is NULL AND entity2.field2 LIKE :search)',
            ],
            [
                'test',
                new DoctrineDescriptor('test1', 'test2'),
                new DoctrineDescriptor('test3', 'test4'),
                '(CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END)',
                [],
                'test1.test2 LIKE :search OR (test1.test2 is NULL AND test3.test4 LIKE :search)',
            ],
            [
                'test',
                new DoctrineDescriptor(
                    'test1',
                    'test2',
                    [
                        'entity1' => new DoctrineJoinDescriptor(
                            'entity1',
                            'entity.relation'
                        ),
                    ]
                ),
                new DoctrineDescriptor('test3', 'test4'),
                '(CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END)',
                [
                    'entity1' => new DoctrineJoinDescriptor(
                        'entity1',
                        'entity.relation'
                    ),
                ],
                'test1.test2 LIKE :search OR (test1.test2 is NULL AND test3.test4 LIKE :search)',
            ],
            [
                'test',
                new DoctrineDescriptor(
                    'test1',
                    'test2'
                ),
                new DoctrineDescriptor(
                    'test3',
                    'test4',
                    [
                        'entity1' => new DoctrineJoinDescriptor(
                            'entity1',
                            'entity.relation'
                        ),
                    ]
                ),
                '(CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END)',
                [
                    'entity1' => new DoctrineJoinDescriptor(
                        'entity1',
                        'entity.relation'
                    ),
                ],
                'test1.test2 LIKE :search OR (test1.test2 is NULL AND test3.test4 LIKE :search)',
            ],
            [
                'test',
                new DoctrineDescriptor(
                    'test1',
                    'test2',
                    [
                        'test5' => new DoctrineJoinDescriptor(
                            'test6',
                            'test7'
                        ),
                    ]
                ),
                new DoctrineDescriptor(
                    'test3',
                    'test4',
                    [
                        'test8' => new DoctrineJoinDescriptor(
                            'test9',
                            'test10'
                        ),
                    ]
                ),
                '(CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END)',
                [
                    'test5' => new DoctrineJoinDescriptor(
                        'test6',
                        'test7'
                    ),
                    'test8' => new DoctrineJoinDescriptor(
                        'test9',
                        'test10'
                    ),
                ],
                'test1.test2 LIKE :search OR (test1.test2 is NULL AND test3.test4 LIKE :search)',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function testComplete($name, $case1, $case2, $select, $joins, $search): void
    {
        $fieldDescriptor = new DoctrineCaseFieldDescriptor($name, $case1, $case2);

        $this->assertEquals($select, $fieldDescriptor->getSelect());
        $this->assertEquals($joins, $fieldDescriptor->getJoins());
        $this->assertEquals($name, $fieldDescriptor->getName());
        $this->assertEquals($search, $fieldDescriptor->getSearch());
    }
}
