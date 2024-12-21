<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Listing;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\Listing\ListQueryBuilder;

class ListQueryBuilderTest extends TestCase
{
    public function testFind(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            [],
            [],
            []
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM Sulu\Bundle\CoreBundle\Entity\Example u', $dql);
    }

    public function testFindWithFields(): void
    {
        $builder = new ListQueryBuilder(
            [],
            ['field1', 'field2', 'field3'],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            ['field1', 'field2', 'field3'],
            [],
            [],
            []
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u.field1, u.field2, u.field3 FROM Sulu\Bundle\CoreBundle\Entity\Example u', $dql);
    }

    public function testFindWithSorting(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            ['sortField' => 'ASC'],
            [],
            []
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM Sulu\Bundle\CoreBundle\Entity\Example u ORDER BY u.sortField ASC', $dql);
    }

    public function testFindWithWhere(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            [],
            ['field1' => 1, 'field2' => 2],
            []
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM Sulu\Bundle\CoreBundle\Entity\Example u WHERE u.field1 = 1 AND u.field2 = 2', $dql);
    }

    public function testFindWithSearch(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            [],
            [],
            ['field']
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM Sulu\Bundle\CoreBundle\Entity\Example u WHERE (u.field LIKE :search)', $dql);
    }

    public function testFindWithWhereAndSearch(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            [],
            ['field1' => 1, 'field2' => 2],
            ['field']
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals(
            'SELECT u FROM Sulu\Bundle\CoreBundle\Entity\Example u WHERE u.field1 = 1 AND u.field2 = 2 AND (u.field LIKE :search)',
            $dql
        );
    }

    public function testFindWithWhereAndNumericSearch(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            [],
            ['field1' => 1, 'field2' => 2],
            ['field1'],
            ['field2', 'field3']
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals(
            'SELECT u FROM Sulu\Bundle\CoreBundle\Entity\Example u WHERE u.field1 = 1 AND u.field2 = 2 AND (u.field1 LIKE :search OR u.field2 = :strictSearch OR u.field3 = :strictSearch)',
            $dql
        );
    }

    public function testFindWithJoins(): void
    {
        $builder = new ListQueryBuilder(
            ['object', 'otherobject'],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            ['object_field1', 'object_field2', 'otherobject_field3'],
            [],
            [],
            []
        );

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals(
            'SELECT object.field1 object_field1, object.field2 object_field2, otherobject.field3 otherobject_field3 ' .
            'FROM Sulu\Bundle\CoreBundle\Entity\Example u LEFT JOIN u.object object LEFT JOIN u.otherobject otherobject',
            $dql
        );
    }

    public function testCounting(): void
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'Sulu\Bundle\CoreBundle\Entity\Example',
            [],
            [],
            [],
            []
        );

        $builder->justCount('u.id', 'total');

        $dql = \str_replace(' ,', ',', \trim(\preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT COUNT(u.id) as total FROM Sulu\Bundle\CoreBundle\Entity\Example u', $dql);
    }
}
