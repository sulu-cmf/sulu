<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ListRestHelperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    protected $requestStack;

    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
    }

    public static function dataFieldsProvider()
    {
        return [
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'searchFields' => 'title',
                        'limit' => 20,
                        'page' => 1,
                    ],
                    [],
                    [
                        '_format' => 'csv',
                    ]
                ),
                [
                    'fields' => ['one', 'two', 'three'],
                    'searchPattern' => 'now',
                    'sortColumn' => null,
                    'sortOrder' => 'asc',
                    'searchFields' => ['title'],
                    'limit' => 20,
                    'offset' => 0,
                    'ids' => null,
                    'excludedIds' => [],
                    'filters' => [],
                ],
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'searchFields' => 'title',
                        'page' => 1,
                        'ids' => '',
                        'excludedIds' => '',
                    ],
                    [],
                    [
                        '_format' => 'csv',
                    ]
                ),
                [
                    'fields' => ['one', 'two', 'three'],
                    'searchPattern' => 'now',
                    'sortColumn' => null,
                    'sortOrder' => 'asc',
                    'searchFields' => ['title'],
                    'limit' => null,
                    'offset' => 0,
                    'ids' => [],
                    'excludedIds' => [],
                    'filters' => [],
                ],
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'page' => 1,
                        'ids' => 'id1,id2',
                        'excludedIds' => 'id3,id4',
                    ],
                    [],
                    []
                ),
                [
                    'fields' => ['one', 'two', 'three'],
                    'searchPattern' => 'now',
                    'sortColumn' => null,
                    'sortOrder' => 'asc',
                    'searchFields' => [],
                    'limit' => 2,
                    'offset' => 0,
                    'ids' => ['id1', 'id2'],
                    'excludedIds' => ['id3', 'id4'],
                    'filters' => [],
                ],
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'page' => 1,
                        'limit' => 1,
                        'ids' => 'id1,id2',
                        'excludedIds' => 'id3,id4',
                        'filter' => ['one' => 1],
                    ],
                    [],
                    []
                ),
                [
                    'fields' => ['one', 'two', 'three'],
                    'searchPattern' => 'now',
                    'sortColumn' => null,
                    'sortOrder' => 'asc',
                    'searchFields' => [],
                    'limit' => 1,
                    'offset' => 0,
                    'ids' => ['id1', 'id2'],
                    'excludedIds' => ['id3', 'id4'],
                    'filters' => ['one' => 1],
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataFieldsProvider')]
    public function testGetFields($request, $expected): void
    {
        $this->requestStack->getCurrentRequest()->willReturn($request);
        $helper = new ListRestHelper($this->requestStack->reveal());

        $this->assertEquals($expected['fields'], $helper->getFields());
        $this->assertEquals($expected['sortColumn'], $helper->getSortColumn());
        $this->assertEquals($expected['sortOrder'], $helper->getSortOrder());
        $this->assertEquals($expected['searchPattern'], $helper->getSearchPattern());
        $this->assertEquals($expected['searchFields'], $helper->getSearchFields());
        $this->assertEquals($expected['limit'], $helper->getLimit());
        $this->assertEquals($expected['offset'], $helper->getOffset());
        $this->assertEquals($expected['ids'], $helper->getIds());
        $this->assertEquals($expected['excludedIds'], $helper->getExcludedIds());
        $this->assertEquals($expected['filters'], $helper->getFilter());
    }
}
