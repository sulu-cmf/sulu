// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {translate} from '../../../utils/Translator';
import userStore from '../../../stores/userStore';
import SingleListOverlay from '../../SingleListOverlay';
import List from '../List';
import ListStore from '../stores/ListStore';
import listAdapterRegistry from '../registries/listAdapterRegistry';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';
import listFieldTransformerRegistry from '../registries/listFieldTransformerRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import TableAdapter from '../adapters/TableAdapter';
import FolderAdapter from '../adapters/FolderAdapter';
import StringFieldTransformer from '../fieldTransformers/StringFieldTransformer';
import ColumnListAdapter from '../adapters/ColumnListAdapter';

let mockStructureStrategyData;
let mockStructureStrategyVisibleItems;

jest.mock('../../../stores/userStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

beforeEach(() => {
    userStore.getPersistentSetting.mockReturnValue(undefined);
});

jest.mock('../stores/ListStore', () => {
    return jest.fn(function(
        resourceKey,
        listKey,
        userSettingsKey,
        observableOptions = {},
        options = {},
        metadataOptions = {}
    ) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.filterableFields = {};
        this.filterOptions = {
            get: jest.fn().mockReturnValue({}),
        };

        this.setPage = jest.fn();
        this.setActive = jest.fn();
        this.activeItems = [];
        this.activate = jest.fn();
        this.active = {
            get: jest.fn(),
        };
        this.deactivate = jest.fn();
        this.delete = jest.fn();
        this.deleteSelection = jest.fn();
        this.order = jest.fn();
        this.sort = jest.fn();
        this.sortColumn = {
            get: jest.fn(),
        };
        this.sortOrder = {
            get: jest.fn(),
        };
        this.searchTerm = {
            get: jest.fn(),
        };
        this.limit = {
            get: jest.fn().mockReturnValue(10),
        };
        this.setLimit = jest.fn();
        this.updateLoadingStrategy = jest.fn();
        this.updateStructureStrategy = jest.fn();
        this.getPage = jest.fn().mockReturnValue(4);
        this.pageCount = 7;
        this.selections = [];
        this.selectionIds = [];
        this.loading = false;
        this.userSchema = {
            title: {
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Title',
            },
        };
        this.findById = jest.fn();
        this.select = jest.fn();
        this.deselect = jest.fn();
        this.selectVisibleItems = jest.fn();
        this.deselectVisibleItems = jest.fn();
        this.updateLoadingStrategy = jest.fn();
        this.structureStrategy = {
            data: mockStructureStrategyData,
            visibleItems: mockStructureStrategyVisibleItems,
        };
        this.data = this.structureStrategy.data;
        this.visibleItems = this.structureStrategy.visibleItems;
        this.search = jest.fn();
        this.filter = jest.fn();
        this.move = jest.fn();
        this.copy = jest.fn();

        mockExtendObservable(this, {
            copying: false,
            ordering: false,
        });
    });
});

jest.mock('../registries/listAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../SingleListOverlay', () => jest.fn(() => null));
class LoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn();
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;
    visibleItems: Array<Object>;

    addItem = jest.fn();
    clear = jest.fn();
    findById = jest.fn();
    order = jest.fn();
    remove = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    static icon = 'su-th-large';

    render() {
        return (
            <div>Test Adapter</div>
        );
    }
}

beforeEach(() => {
    mockStructureStrategyData = [];
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);

    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render Loader instead of Adapter if nothing was loaded yet', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;

    expect(render(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render permission hint if permissions are missing', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;
    listStore.forbidden = true;

    expect(render(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Do not render Loader instead of Adapter if no page count is given', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = undefined;

    expect(render(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render toolbar with with search field and actions', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const listActions = [
        {
            disabled: true,
            icon: 'su-upload',
            label: translate('sulu_admin.upload'),
            onClick: jest.fn(),
        },
        {
            disabled: false,
            icon: 'su-refresh',
            label: translate('sulu_admin.refresh'),
            onClick: jest.fn(),
        },
    ];

    expect(render(<List actions={listActions} adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render toolbar with given toolbar class', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = undefined;

    const list = shallow(<List adapters={['table']} store={listStore} toolbarClassName="test-class" />);

    expect(list.find('.toolbar').prop('className')).toEqual(expect.stringContaining('test-class'));
});

test('Do not render toolbar if list is not searchable and adapter has column options but List deactivated them', () => {
    class NewTestAdapter extends TestAdapter {
        static hasColumnOptions = true;
    }
    listAdapterRegistry.get.mockReturnValue(NewTestAdapter);

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = shallow(<List adapters={['table']} searchable={false} showColumnOptions={false} store={listStore} />);

    expect(list.find('.toolbar').exists()).toBeFalsy();
});

test('Do not render toolbar if list is not searchable and adapter has no column options', () => {
    class NewTestAdapter extends TestAdapter {
        static hasColumnOptions = false;
    }
    listAdapterRegistry.get.mockReturnValue(NewTestAdapter);

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = shallow(<List adapters={['table']} searchable={false} store={listStore} />);

    expect(list.find('.toolbar').exists()).toBeFalsy();
});

test('Render toolbar if list is not searchable but adapter has column options', () => {
    class NewTestAdapter extends TestAdapter {
        static hasColumnOptions = true;
    }
    listAdapterRegistry.get.mockReturnValue(NewTestAdapter);

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = shallow(<List adapters={['table']} searchable={false} store={listStore} />);

    expect(list.find('.toolbar').exists()).toBeTruthy();
});

test('Render toolbar with multiple adapters if list is not searchable and adapter has no column options', () => {
    class NewTestAdapter extends TestAdapter {
        static hasColumnOptions = false;
    }
    listAdapterRegistry.get.mockReturnValue(NewTestAdapter);

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = shallow(<List adapters={['table', 'other-table']} searchable={false} store={listStore} />);

    expect(list.find('.toolbar').exists()).toBeTruthy();
});

test('Render TableAdapter with correct values', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.active.get.mockReturnValue(3);
    listStore.selectionIds.push(1, 3);
    const editClickSpy = jest.fn();

    const list = shallow(<List adapters={['table']} onItemClick={editClickSpy} store={listStore} />);

    expect(list.find('Search')).not.toBeUndefined();

    const tableAdapter = list.find('TableAdapter');

    expect(tableAdapter.prop('actions')).toEqual(undefined);
    expect(tableAdapter.prop('data')).toEqual([{'id': 1, 'title': 'value'}]);
    expect(tableAdapter.prop('active')).toEqual(3);
    expect(tableAdapter.prop('activeItems')).toBe(listStore.activeItems);
    expect(tableAdapter.prop('selections')).toEqual([1, 3]);
    expect(tableAdapter.prop('schema')).toEqual({
        title: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    });
    expect(tableAdapter.prop('onItemClick')).toBe(editClickSpy);
    expect(tableAdapter.prop('onItemSelectionChange')).toBeInstanceOf(Function);
    expect(tableAdapter.prop('onAllSelectionChange')).toBeInstanceOf(Function);
});

test('Render TableAdapter with itemActions', () => {
    const actionsProvider = () => [
        {
            icon: 'su-process',
            onClick: undefined,
        },
    ];

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // eslint-disable-next-line react/jsx-no-bind
    const list = shallow(<List adapters={['table']} itemActionsProvider={actionsProvider} store={listStore} />);

    const tableAdapter = list.find('TableAdapter');
    expect(tableAdapter.prop('itemActionsProvider')).toEqual(actionsProvider);
});

test('Render the adapter in non-selectable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} selectable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onItemSelectionChange')).toEqual(undefined);
    expect(list.find('TestAdapter').prop('onAllSelectionChange')).toEqual(undefined);
});

test('Render the adapter in non-deletable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} deletable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestItemDelete')).toEqual(undefined);
});

test('Render the adapter in non-movable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} movable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestItemMove')).toEqual(undefined);
});

test('Render the adapter in non-copyable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} copyable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestItemCopy')).toEqual(undefined);
});

test('Render the adapter in non-orderable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} orderable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestOrderItem')).toEqual(undefined);
});

test('Render the adapter in non-searchable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        render(<List adapters={['test']} header={<h1>Title</h1>} searchable={false} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter in non-searchable mode if searchable is set to true but adapter does not support it', () => {
    class TestAdapter extends AbstractAdapter {
        static LoadingStrategy = LoadingStrategy;
        static StructureStrategy = StructureStrategy;
        static icon = 'su-th-large';
        static searchable = false;

        render() {
            return (
                <div>Test Adapter</div>
            );
        }
    }

    listAdapterRegistry.get.mockReturnValue(TestAdapter);

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        render(<List adapters={['test']} header={<h1>Title</h1>} searchable={true} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter in disabled state', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        render(<List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter with filters', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            filterType: 'text',
            label: 'Title',
        },
        created: {
            filterType: 'datetime',
            label: 'Created at',
        },
        changed: {
            label: 'Changed at',
        },
    };

    listFieldFilterTypeRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'datetime':
            case 'text':
                return class {
                    getFormNode = jest.fn();
                    getValueNode = jest.fn();
                    setValue = jest.fn();
                };
        }
    });

    listStore.filterOptions.get.mockReturnValue({
        title: undefined,
        created: undefined,
    });

    expect(
        mount(<List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />).render()
    ).toMatchSnapshot();
});

test('Render the adapter with filters but filterable disabled', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            filterType: 'text',
            label: 'Title',
        },
        created: {
            filterType: 'datetime',
            label: 'Created at',
        },
        changed: {
            label: 'Changed at',
        },
    };

    listFieldFilterTypeRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'datetime':
            case 'text':
                return class {
                    getFormNode = jest.fn();
                    getValueNode = jest.fn();
                    setValue = jest.fn();
                };
        }
    });

    listStore.filterOptions.get.mockReturnValue({
        title: undefined,
        created: undefined,
    });

    expect(
        mount(
            <List adapters={['test']} disabled={true} filterable={false} header={<h1>Title</h1>} store={listStore} />
        ).render()
    ).toMatchSnapshot();
});

test('Pass the given disabledIds to the adapter', () => {
    const disabledIds = [1, 3];
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} disabledIds={disabledIds} store={listStore} />);

    expect(list.find('TestAdapter').prop('disabledIds')).toEqual(disabledIds);
});

test('Pass given disabledIds and ids of items that fulfill given itemDisabledCondition to the adapter', () => {
    mockStructureStrategyVisibleItems = [
        {
            id: 1,
            status: 'active',
        },
        {
            id: 2,
            status: 'active',
        },
        {
            id: 3,
            status: 'active',
        },
        {
            id: 4,
            status: 'inactive',
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(
        <List
            adapters={['test']}
            disabledIds={[1, 3]}
            itemDisabledCondition='status == "inactive"'
            store={listStore}
        />
    );

    expect(list.find('TestAdapter').prop('disabledIds')).toEqual([1, 3, 4]);
});

test('Pass adapterOptions to the adapter', () => {
    const adapterOptions = {table: {show_header: true}, test: {skin: 'light'}};
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapterOptions={adapterOptions} adapters={['test']} store={listStore} />);

    expect(list.find('TestAdapter').prop('adapterOptions')).toEqual({skin: 'light'});
});

test('Pass undefined as adapterOptions to the adapter if no options for current adapter are passed', () => {
    const adapterOptions = {table: {skin: 'flat'}};
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapterOptions={adapterOptions} adapters={['test']} store={listStore} />);

    expect(list.find('TestAdapter').prop('adapterOptions')).toEqual(undefined);
});

test('Call activate on store if item is activated', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} store={listStore} />);

    list.find('TestAdapter').prop('onItemActivate')(5);

    expect(listStore.activate).toBeCalledWith(5);
});

test('Do not call activate if item is activated but disabled and allowActivateForDisabledItems is false', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(
        <List adapters={['test']} allowActivateForDisabledItems={false} disabledIds={[5]} store={listStore} />
    );

    list.find('TestAdapter').prop('onItemActivate')(5);
    list.find('TestAdapter').prop('onItemActivate')(7);

    expect(listStore.activate).not.toBeCalledWith(5);
    expect(listStore.activate).toBeCalledWith(7);
});

test('Do not call activate if item fulfills itemDisabledCondition and allowActivateForDisabledItems is false', () => {
    mockStructureStrategyVisibleItems = [
        {
            id: 1,
            status: 'active',
        },
        {
            id: 2,
            status: 'inactive',
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(
        <List
            adapters={['test']}
            allowActivateForDisabledItems={false}
            itemDisabledCondition='status == "inactive"'
            store={listStore}
        />
    );

    list.find('TestAdapter').prop('onItemActivate')(1);
    list.find('TestAdapter').prop('onItemActivate')(2);

    expect(listStore.activate).toBeCalledWith(1);
    expect(listStore.activate).not.toBeCalledWith(2);
});

test('Call deactivate on store if item is deactivated', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} store={listStore} />);

    list.find('TestAdapter').prop('onItemDeactivate')(5);

    expect(listStore.deactivate).toBeCalledWith(5);
});

test('Pass sortColumn and sortOrder to adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.sortColumn.get.mockReturnValue('title');
    listStore.sortOrder.get.mockReturnValue('asc');
    const list = shallow(<List adapters={['test']} store={listStore} />);

    expect(list.find('TestAdapter').props()).toEqual(expect.objectContaining({
        sortColumn: 'title',
        sortOrder: 'asc',
    }));
});

test('Pass options to adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const listAdapterOptions = {test: 'value'};
    listAdapterRegistry.getOptions.mockReturnValue(listAdapterOptions);

    const list = shallow(<List adapters={['test']} store={listStore} />);

    expect(list.find('TestAdapter').props()).toEqual(expect.objectContaining({
        options: listAdapterOptions,
    }));
});

test('Pass correct options and metadataOptions to SingleListOverlays', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)}, {}, {id: 1});

    const list = shallow(<List adapters={['test']} store={listStore} />);

    expect(list.find(SingleListOverlay).at(0).prop('reloadOnOpen')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('reloadOnOpen')).toEqual(true);

    expect(list.find(SingleListOverlay).at(0).prop('metadataOptions')).toEqual({id: 1});
    expect(list.find(SingleListOverlay).at(1).prop('metadataOptions')).toEqual({id: 1});
});

test('Selecting and deselecting items should update store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.structureStrategy.data.splice(0, listStore.structureStrategy.data.length);
    listStore.structureStrategy.data.push(
        {id: 1},
        {id: 2},
        {id: 3}
    );

    listStore.findById.mockReturnValueOnce({id: 1}).mockReturnValueOnce({id: 2}).mockReturnValueOnce({id: 1});

    const list = mount(<List adapters={['table']} store={listStore} />);

    const checkboxes = list.find('input[type="checkbox"]');
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    checkboxes.at(1).getDOMNode().checked = true;
    checkboxes.at(2).getDOMNode().checked = true;
    checkboxes.at(1).simulate('change', {currentTarget: {checked: true}});
    expect(listStore.findById).toBeCalledWith(1);
    expect(listStore.select).toBeCalledWith({id: 1});
    checkboxes.at(2).simulate('change', {currentTarget: {checked: true}});
    expect(listStore.findById).toBeCalledWith(2);
    expect(listStore.select).toBeCalledWith({id: 2});
    checkboxes.at(1).simulate('change', {currentTarget: {checked: false}});
    expect(listStore.findById).toBeCalledWith(1);
    expect(listStore.deselect).toBeCalledWith({id: 1});
});

test('Selecting and unselecting all visible items should update store', () => {
    mockStructureStrategyVisibleItems = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = mount(<List adapters={['table']} store={listStore} />);

    const headerCheckbox = list.find('input[type="checkbox"]').at(0);
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    headerCheckbox.getDOMNode().checked = true;

    headerCheckbox.simulate('change', {currentTarget: {checked: true}});
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).toBeCalledWith({id: 2});
    expect(listStore.select).toBeCalledWith({id: 3});

    headerCheckbox.simulate('change', {currentTarget: {checked: false}});
    expect(listStore.deselect).toBeCalledWith({id: 1});
    expect(listStore.deselect).toBeCalledWith({id: 2});
    expect(listStore.deselect).toBeCalledWith({id: 3});
});

test('Should select and unselect all non-disabled items when adapter fires onAllSelectionChange callback', () => {
    mockStructureStrategyVisibleItems = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = mount(<List adapters={['table']} disabledIds={[2]} store={listStore} />);

    list.find(TableAdapter).props().onAllSelectionChange(true);
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).not.toBeCalledWith({id: 2});
    expect(listStore.select).toBeCalledWith({id: 3});

    list.find(TableAdapter).props().onAllSelectionChange(false);
    expect(listStore.deselect).toBeCalledWith({id: 1});
    expect(listStore.deselect).not.toBeCalledWith({id: 2});
    expect(listStore.deselect).toBeCalledWith({id: 3});
});

test('Clicking a header cell should sort the table', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const headerCell = list.find('th button').at(0);
    headerCell.simulate('click');
    expect(listStore.sort).toBeCalledWith('title', 'asc');
});

test('Trigger a search should call search on the store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    list.find('Search').prop('onSearch')('search-value');
    expect(listStore.search).toBeCalledWith('search-value');
});

test('Trigger a filter should call filter on the store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            label: 'Title',
        },
    };

    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    const list = mount(<List adapters={['table']} store={listStore} />);

    list.find('FieldFilter').prop('onChange')({title: undefined});
    expect(listStore.filter).toBeCalledWith({title: undefined});
});

test('Should start with adapter from user settings', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });

    userStore.getPersistentSetting.mockReturnValue('folder');

    const list = mount(<List adapters={['table', 'folder']} store={listStore} />);

    expect(userStore.getPersistentSetting).toBeCalledWith('sulu_admin.list.test.list_test.adapter');
    expect(list.find('AdapterSwitch').length).toBe(1);
    expect(list.find('FolderAdapter').length).toBe(1);
});

test('Switching the adapter should render the correct adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    const list = mount(<List adapters={['table', 'folder']} store={listStore} />);

    expect(list.find('AdapterSwitch').length).toBe(1);
    expect(list.find('TableAdapter').length).toBe(1);

    list.find('AdapterSwitch Button').at(1).simulate('click');
    expect(list.find('TableAdapter').length).toBe(0);
    expect(list.find('FolderAdapter').length).toBe(1);
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_admin.list.test.list_test.adapter', 'folder');
});

test('ListStore should be initialized correctly on init and update', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    mount(<List adapters={['table', 'folder']} store={listStore} />);
    expect(listStore.updateLoadingStrategy).toBeCalledWith(expect.any(TableAdapter.LoadingStrategy));
    expect(listStore.updateStructureStrategy).toBeCalledWith(expect.any(TableAdapter.StructureStrategy));
});

test('Correct LoadingStrategyOptions should be passed to the LoadingStrategy if paginated prop is set', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    TableAdapter.LoadingStrategy = (jest.fn(): any);

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    mount(<List adapters={['table', 'folder']} paginated={true} store={listStore} />);
    expect(TableAdapter.LoadingStrategy).toBeCalledWith({paginated: true});
});

test('Correct LoadingStrategyOptions should not be passed to the LoadingStrategy if adapter is not paginatable', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    ColumnListAdapter.LoadingStrategy = (jest.fn(): any);

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'column_list':
                return ColumnListAdapter;
        }
    });
    mount(<List adapters={['column_list']} paginated={true} store={listStore} />);
    expect(ColumnListAdapter.LoadingStrategy).toBeCalledWith({paginated: false});
});

test('ListStore should be updated with current active element', () => {
    listAdapterRegistry.get.mockReturnValue(class TestAdapter extends AbstractAdapter {
        static LoadingStrategy = class {
            destroy = jest.fn();
            initialize = jest.fn();
            load = jest.fn();
            reset = jest.fn();
            setStructureStrategy = jest.fn();
        };

        static StructureStrategy = class {
            data = [];
            visibleItems = [];
            addItem = jest.fn();
            clear = jest.fn();
            findById = jest.fn();
            remove = jest.fn();
            order = jest.fn();
        };

        static icon = 'su-th-large';

        constructor(props: *) {
            super(props);

            const {onItemActivate} = this.props;
            if (onItemActivate) {
                onItemActivate('some-uuid');
            }
        }

        render() {
            return null;
        }
    });
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(listStore.active.get()).toBe(undefined);
    mount(<List adapters={['test']} store={listStore} />);

    expect(listStore.activate).toBeCalledWith('some-uuid');
});

test('SingleListOverlay should disappear when onRequestItemCopy callback is called and overlay is closed', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test_list', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    const requestCopyPromise = list.find('TableAdapter').prop('onRequestItemCopy')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('clearSelectionOnClose')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('disabledIds')).toEqual(undefined);
    expect(list.find(SingleListOverlay).at(1).prop('resourceKey')).toEqual('test');
    expect(list.find(SingleListOverlay).at(1).prop('listKey')).toEqual('test_list');

    list.find(SingleListOverlay).at(1).prop('onClose')();
    return requestCopyPromise.then(() => {
        list.update();
        expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(false);

        expect(listStore.copy).not.toBeCalled();
    });
});

test('ListStore should copy item when onRequestItemCopy callback is called and overlay is confirmed', () => {
    const copyPromise = Promise.resolve({id: 9});
    const copyFinishedSpy = jest.fn();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.copy.mockReturnValue(copyPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} onCopyFinished={copyFinishedSpy} store={listStore} />);

    const requestCopyPromise = list.find('TableAdapter').prop('onRequestItemCopy')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('clearSelectionOnClose')).toEqual(true);

    list.find(SingleListOverlay).at(1).prop('onConfirm')({id: 8});
    return requestCopyPromise.then(() => {
        expect(listStore.copy).toBeCalledWith(5, 8, copyFinishedSpy);

        return copyPromise.then(() => {
            list.update();
            expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(false);
        });
    });
});

test('SingleListOverlay should disappear when onRequestItemMove callback is called and overlay is closed', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test_list', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    const requestMovePromise = list.find('TableAdapter').prop('onRequestItemMove')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);
    expect(list.find(SingleListOverlay).at(0).prop('disabledIds')).toEqual([5]);
    expect(list.find(SingleListOverlay).at(0).prop('resourceKey')).toEqual('test');
    expect(list.find(SingleListOverlay).at(0).prop('listKey')).toEqual('test_list');

    list.find(SingleListOverlay).at(0).prop('onClose')();

    return requestMovePromise.then(() => {
        list.update();
        expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(false);

        expect(listStore.move).not.toBeCalled();
    });
});

test('ListStore should move item when onRequestItemMove callback is called and overlay is confirmed', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: false});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestMovePromise = list.find('TableAdapter').prop('onRequestItemMove')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);

    list.find(SingleListOverlay).at(0).prop('onConfirm')({id: 8});
    return requestMovePromise.then(() => {
        expect(listStore.move).toBeCalledWith(5, 8);

        return movePromise.then(() => {
            list.update();
            expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(false);
        });
    });
});

test('ListStore should move item when onRequestItemMove callback is called and permission dialog is confirmed', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: true});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestMovePromise = list.find('TableAdapter').prop('onRequestItemMove')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);

    list.find(SingleListOverlay).at(0).prop('onConfirm')({id: 8});

    list.update();
    expect(list.find('Dialog[title="sulu_security.move_permission_title"]').prop('open')).toEqual(true);
    list.find('Dialog[title="sulu_security.move_permission_title"]').prop('onConfirm')();
    return requestMovePromise.then(() => {
        expect(listStore.move).toBeCalledWith(5, 8);

        return movePromise.then(() => {
            list.update();
            expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(false);
        });
    });
});

test('ListStore should not move when onRequestItemMove callback is called and permission dialog is denied', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: false});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    list.find('TableAdapter').prop('onRequestItemMove')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);

    list.find(SingleListOverlay).at(0).prop('onConfirm')({id: 8, _hasPermissions: true});

    list.update();
    expect(list.find('Dialog[title="sulu_security.move_permission_title"]').prop('open')).toEqual(true);
    list.find('Dialog[title="sulu_security.move_permission_title"]').prop('onCancel')();
    list.update();
    expect(list.find('Dialog[title="sulu_security.move_permission_title"]').prop('open')).toEqual(false);
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);
    expect(listStore.move).not.toBeCalledWith(5, 8);
});

test('Delete warning should disappear when deleting selection was requested and overlay is cancelled', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.selections.push({}, {});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    list.instance().requestSelectionDelete();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 2});

    list.find('Dialog').at(0).prop('onCancel')();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
});

test('ListStore should delete selections when deleting selection was requested and overlay is confirmed', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.selections.push({}, {}, {});
    const deleteSelectionPromise = Promise.resolve();
    // $FlowFixMe
    listStore.deleteSelection.mockReturnValue(deleteSelectionPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    list.instance().requestSelectionDelete();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 3});

    list.find('Dialog').at(0).prop('onConfirm')();

    expect(listStore.deleteSelection).toBeCalledWith();

    return deleteSelectionPromise.then(() => {
        list.update();
        expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
    });
});

test('Delete warning should disappear when onRequestItemDelete callback is called and overlay is cancelled', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onCancel')();
    return requestDeletePromise.then(() => {
        list.update();
        expect(list.find('Dialog').at(1).prop('open')).toEqual(false);

        expect(listStore.delete).not.toBeCalled();
    });
});

test('ListStore should delete item when onRequestItemDelete callback is called and overlay is confirmed', () => {
    const deletePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValue(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);

        return deletePromise.then(() => {
            list.update();
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.find('Dialog').at(2).prop('open')).toEqual(false);
        });
    });
});

test('ListStore should delete linked item when onRequestItemDelete callback is is confirmed twice', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);

        setTimeout(() => {
            list.update();
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.contains('DeleteReferencedResourceDialog'));

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            list.find('DeleteReferencedResourceDialog Dialog Button[skin="primary"]').simulate('click');

            setTimeout(() => {
                expect(listStore.delete).toBeCalledWith(5, {force: true});
                list.update();
                expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
                expect(list.contains('DeleteReferencedResourceDialog')).toBe(false);
                done();
            });
        });
    });
});

test('ListStore should not delete linked item when onRequestItemDelete callback is is confirmed once', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);
        // $FlowFixMe
        listStore.delete.mockReset();

        setTimeout(() => {
            list.update();
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.contains('DeleteReferencedResourceDialog'));
            expect(list.find('DeleteReferencedResourceDialog').find('li')).toHaveLength(2);
            expect(list.find('DeleteReferencedResourceDialog').find('li').at(0).prop('children')).toEqual('Item 1');
            expect(list.find('DeleteReferencedResourceDialog').find('li').at(1).prop('children')).toEqual('Item 2');

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            list.find('DeleteReferencedResourceDialog').prop('onCancel')();

            setTimeout(() => {
                expect(listStore.delete).not.toBeCalled();
                list.update();
                expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
                expect(list.contains('DeleteReferencedResourceDialog')).toBe(false);
                done();
            });
        });
    });
});

test('ListStore should delete linked item when called with allowConflictDeletion value of true', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.deleteSelection.mockReturnValueOnce(deletePromise);
    listStore.selectionIds.push(5);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    list.instance().requestSelectionDelete(true);
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);

    list.find('Dialog').at(0).prop('onConfirm')();

    setTimeout(() => {
        list.update();
        expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
        expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
        expect(list.contains('DeleteReferencedResourceDialog'));

        const deletePromise = Promise.resolve();
        // $FlowFixMe
        listStore.delete.mockReturnValueOnce(deletePromise);
        list.find('DeleteReferencedResourceDialog Dialog Button[skin="primary"]').simulate('click');

        setTimeout(() => {
            expect(listStore.delete).toBeCalledWith(5, {force: true});
            list.update();
            expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.contains('DeleteReferencedResourceDialog')).toBe(false);
            done();
        });
    });
});

test('ListStore should not delete linked item when called with allowConflictDeletion value of false', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.deleteSelection.mockReturnValueOnce(deletePromise);
    listStore.selectionIds.push(5);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    list.instance().requestSelectionDelete(false);
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);

    list.find('Dialog').at(0).prop('onConfirm')();

    setTimeout(() => {
        list.update();
        expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
        expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
        expect(list.contains('DeleteReferencedResourceDialog'));

        list.find('DeleteReferencedResourceDialog Dialog Button[skin="primary"]').simulate('click');

        setTimeout(() => {
            expect(listStore.delete).not.toBeCalledWith(5, {force: true});
            list.update();
            expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.contains('DeleteReferencedResourceDialog')).toBe(false);
            done();
        });
    });
});

test('ListStore should delete item with dependants when onFinish callback called', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1105,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        dependantResourceBatches: [
            {id: 7, resourceKey: 'pages'},
            {id: 8, resourceKey: 'pages'},
        ],
        dependantResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toHaveBeenCalledWith(5);

        setTimeout(() => {
            list.update();
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.contains('DeleteDependantResourcesDialog'));

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            list.find('DeleteDependantResourcesDialog').prop('onFinish')();

            setTimeout(() => {
                expect(listStore.delete).toHaveBeenCalledWith(5);
                expect(listStore.delete).toHaveBeenCalledTimes(2);
                list.update();
                expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
                expect(list.contains('DeleteDependantResourcesDialog')).toBe(false);
                done();
            });
        });
    });
});

test('ListStore should not delete item with dependants when onCancel callback called', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1105,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        dependantResourceBatches: [
            {id: 7, resourceKey: 'pages'},
            {id: 8, resourceKey: 'pages'},
        ],
        dependantResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toHaveBeenCalledWith(5);

        setTimeout(() => {
            list.update();
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
            expect(list.contains('DeleteDependantResourcesDialog'));

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            list.find('DeleteDependantResourcesDialog').prop('onCancel')();

            setTimeout(() => {
                expect(listStore.delete).toHaveBeenCalledTimes(1);
                list.update();
                expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
                expect(list.contains('DeleteDependantResourcesDialog')).toBe(false);
                done();
            });
        });
    });
});

test('Order warning should just disappear when onRequestItemOrder callback is called and overlay is cancelled', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestOrderPromise = list.find('TableAdapter').prop('onRequestItemOrder')(5);
    list.update();
    expect(list.find('Dialog[title="sulu_admin.order_warning_title"]').prop('open')).toEqual(true);

    list.find('Dialog[title="sulu_admin.order_warning_title"]').prop('onCancel')();

    return requestOrderPromise.then(() => {
        list.update();
        expect(list.find('Dialog[title="sulu_admin.order_warning_title"]').prop('open')).toEqual(false);

        expect(listStore.order).not.toBeCalled();
    });
});

test('ListStore should order item when onRequestItemOrder callback is called and overlay is confirmed', () => {
    const orderPromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.order.mockReturnValue(orderPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestOrderPromise = list.find('TableAdapter').prop('onRequestItemOrder')(5, 8);
    list.update();
    expect(list.find('Dialog[title="sulu_admin.order_warning_title"]').prop('open')).toEqual(true);
    list.find('Dialog[title="sulu_admin.order_warning_title"]').prop('onConfirm')();

    return requestOrderPromise.then(() => {
        expect(listStore.order).toBeCalledWith(5, 8);

        return orderPromise.then(() => {
            list.update();
            expect(list.find('Dialog[title="sulu_admin.order_warning_title"]').prop('open')).toEqual(false);
        });
    });
});
