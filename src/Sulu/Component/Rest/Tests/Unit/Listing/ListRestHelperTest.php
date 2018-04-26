<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Listing;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Component\Rest\Listing\ListRestHelper;
use Symfony\Component\HttpFoundation\Request;
use \PHPUnit\Framework\TestCase;

class ListRestHelperTest extends TestCase
{
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(ObjectManager::class);
    }

    public function testGetFields()
    {
        $request = new Request([
            'fields' => 'field1,field2,field3',
            'sortBy' => 'id',
            'sortOrder' => 'desc',
            'search' => 'test',
            'searchFields' => 'title',
            'limit' => 10,
            'page' => 3,
        ]);
        $helper = new ListRestHelper($request, $this->em->reveal());

        $this->assertEquals(['field1', 'field2', 'field3'], $helper->getFields());
        $this->assertEquals(['id' => 'desc'], $helper->getSorting());
        $this->assertEquals('test', $helper->getSearchPattern());
        $this->assertEquals(['title'], $helper->getSearchFields());
        $this->assertEquals(10, $helper->getLimit());
        $this->assertEquals(20, $helper->getOffset());
    }
}
