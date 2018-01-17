// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import {observable} from 'mobx';
import Datagrid from '../Datagrid';
import DatagridStore from '../stores/DatagridStore';
import datagridAdapterRegistry from '../registries/DatagridAdapterRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import TableAdapter from '../adapters/TableAdapter';
import FolderAdapter from '../adapters/FolderAdapter';

jest.mock('../stores/DatagridStore', () => jest.fn(function() {
    this.setPage = jest.fn();
    this.setActive = jest.fn();
    this.updateStrategies = jest.fn();
    this.getPage = jest.fn().mockReturnValue(4);
    this.pageCount = 7;
    this.selections = [];
    this.loading = false;
    this.schema = {test: {}};
    this.select = jest.fn();
    this.deselect = jest.fn();
    this.selectEntirePage = jest.fn();
    this.deselectEntirePage = jest.fn();
    this.updateLoadingStrategy = jest.fn();
    this.structureStrategy = {
        data: [
            {
                title: 'value',
                id: 1,
            },
        ],
    };
    this.data = this.structureStrategy.data;
}));

jest.mock('../registries/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    },
}));

class LoadingStrategy {
    load = jest.fn();
    destroy = jest.fn();
    initialize = jest.fn();
    reset = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;

    clear = jest.fn();
    getData = jest.fn();
    enhanceItem = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    render() {
        return (
            <div>Test Adapter</div>
        );
    }
}

beforeEach(() => {
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.get.mockReturnValue(TestAdapter);
});

test('Render TableAdapter with correct values', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);

    const datagridStore = new DatagridStore('test', {page: observable(1)});
    datagridStore.active = 3;
    datagridStore.selections.push(1);
    datagridStore.selections.push(3);
    const editClickSpy = jest.fn();

    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} onItemClick={editClickSpy} />);
    const tableAdapter = datagrid.find('TableAdapter');

    expect(tableAdapter.prop('data')).toEqual([{'id': 1, 'title': 'value'}]);
    expect(tableAdapter.prop('active')).toEqual(3);
    expect(tableAdapter.prop('selections')).toEqual([1, 3]);
    expect(tableAdapter.prop('schema')).toEqual({test: {}});
    expect(tableAdapter.prop('onItemClick')).toBe(editClickSpy);
});

test('Selecting and deselecting items should update store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', {page: observable(1)});
    datagridStore.structureStrategy.data.splice(0, datagridStore.structureStrategy.data.length);
    datagridStore.structureStrategy.data.push(
        {id: 1},
        {id: 2},
        {id: 3}
    );
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const checkboxes = datagrid.find('input[type="checkbox"]');
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    checkboxes.at(1).getDOMNode().checked = true;
    checkboxes.at(2).getDOMNode().checked = true;
    checkboxes.at(1).simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.select).toBeCalledWith(1);
    checkboxes.at(2).simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.select).toBeCalledWith(2);
    checkboxes.at(1).simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.deselect).toBeCalledWith(1);
});

test('Selecting and unselecting all items on current page should update store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', {page: observable(1)});
    datagridStore.structureStrategy.data = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const headerCheckbox = datagrid.find('input[type="checkbox"]').at(0);
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    headerCheckbox.getDOMNode().checked = true;
    headerCheckbox.simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.selectEntirePage).toBeCalledWith();
    headerCheckbox.simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.deselectEntirePage).toBeCalledWith();
});

test('Switching the adapter should render the correct adapter', () => {
    const datagridStore = new DatagridStore('test', {page: observable(1)});

    datagridAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    const datagrid = mount(<Datagrid adapters={['table', 'folder']} store={datagridStore} />);

    expect(datagrid.find('AdapterSwitch').length).toBe(1);
    expect(datagrid.find('TableAdapter').length).toBe(1);

    datagrid.find('AdapterSwitchItem').at(1).simulate('click');
    expect(datagrid.find('TableAdapter').length).toBe(0);
    expect(datagrid.find('FolderAdapter').length).toBe(1);
});

test('DatagridStore should be initialized correctly on init and update', () => {
    const datagridStore = new DatagridStore('test', {page: observable(1)});
    datagridStore.updateStrategies = jest.fn();

    datagridAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    mount(<Datagrid adapters={['table', 'folder']} store={datagridStore} />);
    expect(datagridStore.updateStrategies)
        .toBeCalledWith(expect.any(TableAdapter.LoadingStrategy), expect.any(TableAdapter.StructureStrategy));
});

test('DatagridStore should be updated with current active element', () => {
    datagridAdapterRegistry.get.mockReturnValue(class TestAdapter extends AbstractAdapter {
        static LoadingStrategy = class {
            paginationAdapter = undefined;
            load = jest.fn();
            destroy = jest.fn();
            initialize = jest.fn();
            reset = jest.fn();
        };

        static StructureStrategy = class {
            data = [];
            clear = jest.fn();
            getData = jest.fn();
            enhanceItem = jest.fn();
        };

        componentWillMount() {
            const {onItemActivation} = this.props;
            if (onItemActivation) {
                onItemActivation('some-uuid');
            }
        }

        render() {
            return null;
        }
    });
    const datagridStore = new DatagridStore('test', {page: observable(1)});
    expect(datagridStore.active).toBe(undefined);
    mount(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagridStore.setActive).toBeCalledWith('some-uuid');
});
