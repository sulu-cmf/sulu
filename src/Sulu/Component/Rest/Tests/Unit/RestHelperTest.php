<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\Exception\SearchFieldNotFoundException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\RestHelper;

class RestHelperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var RestHelper
     */
    private $restHelper;

    /**
     * @var ObjectProphecy<ListRestHelper>
     */
    private $listRestHelper;

    /**
     * @var ObjectProphecy<ListBuilderInterface>
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->listRestHelper = $this->prophesize(ListRestHelper::class);
        $this->listRestHelper->getFilter()->willReturn([]);
        $this->listRestHelper->getPage()->willReturn(1);
        $this->listRestHelper->getIds()->willReturn([]);
        $this->listRestHelper->getExcludedIds()->willReturn([]);
        $this->listRestHelper->getFields()->willReturn([]);
        $this->listRestHelper->getSearchPattern()->willReturn(null);
        $this->listRestHelper->getSortColumn()->willReturn(null);
        $this->listRestHelper->getLimit()->willReturn(10);

        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
        $this->listBuilder->limit(Argument::any())->should(function() {});
        $this->listBuilder->setCurrentPage(Argument::any())->should(function() {});
        $this->listBuilder->setFieldDescriptors(Argument::any())->should(function() {});
        $this->listBuilder->setIds(Argument::any())->should(function() {});
        $this->listBuilder->setExcludedIds(Argument::any())->should(function() {});
        $this->listBuilder->filter(Argument::any())->should(function() {});
        $this->listBuilder->setSelectFields(Argument::any())->should(function() {});

        $this->restHelper = new RestHelper($this->listRestHelper->reveal());
    }

    public function testInitializeListBuilderLimit(): void
    {
        $this->listRestHelper->getLimit()->willReturn(10);
        $this->listBuilder->limit(10)->shouldBeCalled();
        $this->listBuilder->limit(10)->shouldBeCalled();

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), []);
    }

    public function testInitializeListBuilderPage(): void
    {
        $this->listRestHelper->getPage()->willReturn(2);
        $this->listBuilder->setCurrentPage(2)->shouldBeCalled();

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), []);
    }

    public function testInitializeListBuilderIds(): void
    {
        $this->listRestHelper->getIds()->willReturn([2, 4, 6]);
        $this->listBuilder->setIds([2, 4, 6])->shouldBeCalled();

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), []);
    }

    public function testInitializeListBuilderExcludedIds(): void
    {
        $this->listRestHelper->getExcludedIds()->willReturn([11, 22]);
        $this->listBuilder->setExcludedIds([11, 22])->shouldBeCalled();

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), []);
    }

    public function testInitializeListBuilderAddFields(): void
    {
        $field1 = $this->prophesize(FieldDescriptor::class);
        $field2 = $this->prophesize(FieldDescriptor::class);

        $this->listRestHelper->getFields()->willReturn(['name', 'desc']);
        $this->listBuilder->addSelectField($field1->reveal())->shouldBeCalled();
        $this->listBuilder->addSelectField($field2->reveal())->shouldBeCalled();

        $this->restHelper->initializeListBuilder(
            $this->listBuilder->reveal(),
            ['name' => $field1->reveal(), 'desc' => $field2->reveal()]
        );
    }

    public function testInitializeListBuilderSetFields(): void
    {
        $field1 = $this->prophesize(FieldDescriptor::class);
        $field2 = $this->prophesize(FieldDescriptor::class);
        $fields = ['name' => $field1->reveal(), 'desc' => $field2->reveal()];

        $this->listBuilder->setSelectFields($fields);

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), $fields);
    }

    public function testInitializeListBuilderAddSearch(): void
    {
        $field1 = $this->prophesize(FieldDescriptor::class);
        $field2 = $this->prophesize(FieldDescriptor::class);

        $this->listRestHelper->getSearchFields()->willReturn(['name', 'desc']);
        $this->listRestHelper->getSearchPattern()->willReturn('searchValue');
        $this->listBuilder->addSearchField($field1->reveal())->shouldBeCalled();
        $this->listBuilder->addSearchField($field2->reveal())->shouldBeCalled();
        $this->listBuilder->search('searchValue')->shouldBeCalled();

        $this->restHelper->initializeListBuilder(
            $this->listBuilder->reveal(),
            ['name' => $field1->reveal(), 'desc' => $field2->reveal()]
        );
    }

    public function testInitializeListBuilderAddSearchWithNonExistingSearchField(): void
    {
        $this->expectException(SearchFieldNotFoundException::class);

        $field1 = $this->prophesize(FieldDescriptor::class);

        $field2 = $this->prophesize(FieldDescriptor::class);

        $this->listRestHelper->getSearchFields()->willReturn(['non-existing']);
        $this->listRestHelper->getSearchPattern()->willReturn('searchValue');

        $this->restHelper->initializeListBuilder(
            $this->listBuilder->reveal(),
            ['name' => $field1->reveal(), 'desc' => $field2->reveal()]
        );
    }

    public function testInitializeListBuilderAddSearchWithoutSearchFields(): void
    {
        $field1 = $this->prophesize(FieldDescriptor::class);
        $field2 = $this->prophesize(FieldDescriptor::class);
        $field3 = $this->prophesize(FieldDescriptor::class);

        $field1->getSearchability()->willReturn(FieldDescriptorInterface::SEARCHABILITY_YES);
        $field1->getName()->willReturn('name');

        $field2->getSearchability()->willReturn(FieldDescriptorInterface::SEARCHABILITY_YES);
        $field2->getName()->willReturn('desc');

        $field3->getSearchability()->willReturn(FieldDescriptorInterface::SEARCHABILITY_NO);

        $this->listRestHelper->getSearchFields()->willReturn([]);
        $this->listRestHelper->getSearchPattern()->willReturn('searchValue');

        $this->listBuilder->addSearchField($field1->reveal())->shouldBeCalled();
        $this->listBuilder->addSearchField($field2->reveal())->shouldBeCalled();
        $this->listBuilder->search('searchValue')->shouldBeCalled();

        $this->restHelper->initializeListBuilder(
            $this->listBuilder->reveal(),
            ['name' => $field1->reveal(), 'desc' => $field2->reveal(), 'test' => $field3->reveal()]
        );
    }

    public function testInitializeListBuilderAddFilter(): void
    {
        $this->listRestHelper->getFilter()->willReturn(['name' => 'Max Mustermann']);
        $this->listBuilder->filter(['name' => 'Max Mustermann'])->shouldBeCalled();

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), []);
    }

    public function testInitializeListBuilderSort(): void
    {
        $field = $this->prophesize(FieldDescriptor::class);

        $this->listRestHelper->getSortColumn()->willReturn('name');
        $this->listRestHelper->getSortOrder()->willReturn('ASC');
        $this->listBuilder->sort($field, 'ASC')->shouldBeCalled();

        $this->restHelper->initializeListBuilder($this->listBuilder->reveal(), ['name' => $field->reveal()]);
    }

    public function testprocessSubEntitiesEmpty(): void
    {
        $mock = $this->getMockBuilder(MockInterface::class)->getMock();
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->never())->method('add');
        $mock->expects($this->never())->method('get');

        $get = function() use ($mock) {
            $mock->get();
        };

        $delete = function() use ($mock) {
            $mock->delete();
        };

        $update = function() use ($mock) {
            $mock->update();
        };

        $add = function() use ($mock) {
            $mock->add();
        };

        $this->restHelper->processSubEntities([], [], $get, $add, $update, $delete);
    }

    public function testprocessSubEntitiesWithDelete(): void
    {
        $mockedObject = $this->getMockBuilder(MockInterface::class)->getMock();
        $mockedObject->expects($this->any())->method('getId')->willReturn(1);

        $mock = $this->getMockBuilder(MockInterface::class)->getMock();
        $mock->expects($this->once())->method('delete');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->never())->method('add');
        $mock->expects($this->never())->method('get');

        $get = function() use ($mock) {
            $mock->get();
        };

        $delete = function() use ($mock) {
            $mock->delete();
        };

        $update = function() use ($mock) {
            $mock->update();
        };

        $add = function() use ($mock) {
            $mock->add();
        };

        $this->restHelper->processSubEntities(
            [
                $mockedObject,
            ],
            [],
            $get,
            $add,
            $update,
            $delete
        );
    }

    public function testprocessSubEntitiesWithUpdate(): void
    {
        $mockedObject = $this->getMockBuilder(MockInterface::class)->getMock();
        $mockedObject->expects($this->any())->method('getId')->willReturn(1);

        $mock = $this->getMockBuilder(MockInterface::class)->getMock();
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->once())->method('update');
        $mock->expects($this->never())->method('add');
        $mock->expects($this->once())->method('get')->willReturn($mockedObject->getId());

        $get = function() use ($mock) {
            return $mock->get();
        };

        $delete = function() use ($mock) {
            $mock->delete();
        };

        $update = function() use ($mock) {
            $mock->update();
        };

        $add = function() use ($mock) {
            $mock->add();
        };

        $this->restHelper->processSubEntities(
            [
                $mockedObject,
            ],
            [
                [
                    'id' => 1,
                ],
            ],
            $get,
            $add,
            $update,
            $delete
        );
    }

    public function testprocessSubEntitiesWithAdd(): void
    {
        $mock = $this->getMockBuilder(MockInterface::class)->getMock();
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->once())->method('add');
        $mock->expects($this->never())->method('get');

        $get = function() use ($mock) {
            $mock->get();
        };

        $delete = function() use ($mock) {
            $mock->delete();
        };

        $update = function() use ($mock) {
            $mock->update();
        };

        $add = function() use ($mock) {
            $mock->add();
        };

        $this->restHelper->processSubEntities(
            [],
            [
                [
                    'id' => 1,
                ],
            ],
            $get,
            $add,
            $update,
            $delete
        );
    }

    public function testCompareEntitiesWithData(): void
    {
        $mockedObject = $this->getMockBuilder(MockInterface::class)->getMock();
        $mockedObject->expects($this->any())->method('getId')->willReturn(1);
        $mockedObject->expects($this->any())->method('getValue')->willReturn(2);

        $mockedObject2 = clone $mockedObject;
        $mockedObject3 = clone $mockedObject;

        $mock = $this->getMockBuilder(MockInterface::class)->getMock();
        $mock->expects($this->once())->method('delete');
        $mock->expects($this->any())->method('update');
        $mock->expects($this->once())->method('add');
        $mock->expects($this->any())->method('get');

        $get = function($entity, $data) {
            return
                (isset($data['id']) && $data['id'] === $entity->getId())
                || (isset($data['value']) && $data['value'] === $entity->getValue());
        };

        $delete = function() use ($mock) {
            $mock->delete();

            return true;
        };

        $update = function() use ($mock) {
            $mock->update();

            return true;
        };

        $add = function() use ($mock) {
            $mock->add();

            return true;
        };

        $this->restHelper->compareEntitiesWithData(
            [
                $mockedObject,
                $mockedObject2,
                $mockedObject3,
            ],
            [
                [
                    'id' => 1,
                    'value' => 3,
                ],
                [
                    'id' => 2,
                ],
                [
                    'value' => 2,
                ],
            ],
            $get,
            $add,
            $update,
            $delete
        );
    }
}
