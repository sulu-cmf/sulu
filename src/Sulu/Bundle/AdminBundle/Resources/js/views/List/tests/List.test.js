/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import TableAdapter from '../../../containers/List/adapters/TableAdapter';
import listFieldTransformRegistry from '../../../containers/List/registries/ListFieldTransformerRegistry';
import StringFieldTransformer from '../../../containers/List/fieldTransformers/StringFieldTransformer';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../../services/ResourceRequester/registries/ResourceRouteRegistry', () => ({
    getListUrl: jest.fn()
        .mockReturnValue('testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'),
}));

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../containers/List/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../stores/UserStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

jest.mock(
    '../../../containers/List/stores/ListStore',
    () => jest.fn(function(resourceKey, listKey, userSettingsKey, observableOptions, options, metadataOptions) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.loading = false;
        this.pageCount = 3;
        this.active = {
            get: jest.fn(),
        };
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
        this.data = [
            {
                id: 1,
                title: 'Title 1',
                description: 'Description 1',
            },
            {
                id: 2,
                title: 'Title 2',
                description: 'Description 2',
            },
        ];
        this.selections = [];
        this.selectionIds = [];
        this.deleteSelection = jest.fn();
        this.getPage = jest.fn().mockReturnValue(2);
        this.userSchema = {
            title: {
                type: 'string',
                sortable: true,
                visibility: 'no',
                label: 'Title',
            },
            description: {
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Description',
            },
        };
        this.destroy = jest.fn();
        this.reset = jest.fn();
        this.reload = jest.fn();
        this.clearSelection = jest.fn();
        this.remove = jest.fn();
        this.moveSelection = jest.fn();

        mockExtendObservable(this, {
            moving: false,
            movingSelection: false,
        });
    })
);

jest.mock(
    '../../../stores/ResourceStore/ResourceStore',
    () => jest.fn(function(resourceKey, id) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.data = {
            id: id,
            title: 'Sulu rocks',
            locale: 'de',
        };
    })
);

jest.mock('../../../containers/List/registries/ListAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../../../containers/List/registries/ListFieldTransformerRegistry', () => ({
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
            case 'sulu_admin.delete':
                return 'Delete';
            case 'sulu_admin.add':
                return 'Add';
            case 'sulu_admin.move_items':
                return 'Move items';
            case 'sulu_admin.move_selected':
                return 'Move selected';
            case 'sulu_snippet.snippets':
                return 'Snippets';
            case 'sulu_admin.export':
                return 'Export';
        }
    },
}));

jest.mock('../../../services/Initializer', () => ({
    initializedTranslationsLocale: true,
}));

beforeEach(() => {
    jest.resetModules();

    const listAdapterRegistry = require('../../../containers/List/registries/ListAdapterRegistry');
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TableAdapter);

    listFieldTransformRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Should render the list with the correct resourceKey', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
            },
        },
    };

    const list = render(<List router={router} title="Test 1" />);
    expect(list).toMatchSnapshot();
});

test('Should render the list with a title', () => {
    const List = require('../List').default;

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
            },
        },
    };

    const list = render(<List router={router} title="Test 2" />);
    expect(list).toMatchSnapshot();
});

test('Pass correct arguments to ToolbarActions', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;

    const ToolbarActionMock1 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
    });

    const ToolbarActionMock2 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
    });

    const ToolbarActionMock3 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
    });

    listToolbarActionRegistry.add('mock1', ToolbarActionMock1);
    listToolbarActionRegistry.add('mock2', ToolbarActionMock2);

    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                toolbarActions: {'mock1': {'test1': 'value1'}, 'mock2': {'test2': 'value2'}},
            },
        },
    };

    const list = shallow(<List router={router} />);

    expect(ToolbarActionMock1).toBeCalledWith(
        list.instance().listStore,
        list.instance(),
        router,
        locales,
        undefined,
        {'test1': 'value1'}
    );
    expect(ToolbarActionMock2).toBeCalledWith(
        list.instance().listStore,
        list.instance(),
        router,
        locales,
        undefined,
        {'test2': 'value2'}
    );
    expect(ToolbarActionMock3).not.toBeCalled();
});

test('Throw error if options are not passed correctly', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;

    const ToolbarActionMock1 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
    });

    listToolbarActionRegistry.add('mock1', ToolbarActionMock1);

    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                toolbarActions: ['mock1'],
            },
        },
    };

    expect(() => shallow(<List router={router} />)).toThrow('but string was given');
});

test('Pass correct arguments with passed ResourceStore to ToolbarActions', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const resourceStore = new ResourceStore('tests', '123-456-789');

    const ToolbarActionMock = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
    });

    listToolbarActionRegistry.add('mock1', ToolbarActionMock);

    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                toolbarActions: {'mock1': {}},
            },
        },
    };

    const list = shallow(<List resourceStore={resourceStore} router={router} />);

    expect(ToolbarActionMock).toBeCalledWith(
        list.instance().listStore,
        list.instance(),
        router,
        locales,
        resourceStore,
        {}
    );
});

test('Should pass correct props to move list overlay', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
                toolbarActions: {'sulu_admin.move': {}},
            },
        },
    };

    const list = shallow(<List router={router} />);

    expect(list.find('SingleListOverlay').props()).toEqual(expect.objectContaining({
        listKey: 'snippets_list',
        options: {includeRoot: true},
        reloadOnOpen: true,
        resourceKey: 'snippets',
    }));
});

test('Should pass the onItemClick callback when an editRoute has been passed', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                editRoute: 'editRoute',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemClick')).toBeInstanceOf(Function);
});

test('Should pass the onItemClick callback if onItemClick prop is set', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List onItemClick={jest.fn()} router={router} />);
    expect(list.find('List').prop('onItemClick')).toBeInstanceOf(Function);
});

test('Should not pass the onItemClick callback if no editRoute has been passed and no onItemClick prop is set', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemClick')).not.toBeInstanceOf(Function);
});

test('Should render the list with the add icon if a addRoute has been passed', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                addRoute: 'addRoute',
                listKey: 'snippets',
                resourceKey: 'snippets',
                toolbarActions: {'sulu_admin.add': {}},
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemAdd')).toBeInstanceOf(Function);
});

test('Should render the list with the add icon if onItemAdd prop is set', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                toolbarActions: {'sulu_admin.add': {}},
            },
        },
    };

    const list = shallow(<List onItemAdd={jest.fn()} router={router} />);
    expect(list.find('List').prop('onItemAdd')).toBeInstanceOf(Function);
});

test('Should render the list without add icon if no addRoute has been passed and onItemAdd prop is not set', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemAdd')).not.toBeInstanceOf(Function);
});

test('Should render the list non-searchable if the searchable option has been passed as false', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                searchable: false,
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('searchable')).toEqual(false);
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const List = require('../List').default;
    const router = {
        route: {
            options: {},
        },
    };

    expect(() => render(<List router={router} />)).toThrow(/mandatory "resourceKey" option/);
});

test('Should throw an error when no listKey is defined in the route options', () => {
    const List = require('../List').default;
    const router = {
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
    };

    expect(() => render(<List router={router} />)).toThrow(/mandatory "listKey" option/);
});

test('Should destroy the store on unmount', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                locales: ['de', 'en'],
                resourceKey: 'snippets',
            },
        },
    };

    const list = mount(<List router={router} />);
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    const listStore = list.instance().listStore;

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('page', page, 1);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('active', listStore.active);
    expect(router.bind).toBeCalledWith('sortColumn', listStore.sortColumn);
    expect(router.bind).toBeCalledWith('sortOrder', listStore.sortOrder);
    expect(router.bind).toBeCalledWith('limit', listStore.limit, 10);

    list.unmount();

    expect(listStore.destroy).toBeCalled();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backRoute: 'backRoute',
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(list.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('backRoute', {locale: 'de'});
});

test('Should propagate errors to toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backRoute: 'backRoute',
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const error = 'This is an error';
    list.instance().errors.push(error);

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.errors.length).toBe(1);
    expect(toolbarConfig.errors[0]).toBe(error);
});

test('Should navigate to defined route on back button click without locale', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backRoute: 'backRoute',
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('backRoute', {});
});

test('Should not render back button when no backRoute is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should render the add button in the toolbar only if an addRoute has been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: {'sulu_admin.add': {}},
            },
        },
    };

    const list = mount(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.items).toEqual(
        expect.arrayContaining(
            [
                expect.objectContaining({icon: 'su-plus-circle', label: 'Add'}),
            ]
        )
    );
});

test('Should navigate when add button is clicked and locales have been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                locales: ['de', 'en'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: {'sulu_admin.add': {}},
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };
    const toolbarConfig = toolbarFunction.call(list.instance());

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toBeCalledWith('addRoute', {locale: 'de'});
});

test('Should navigate without locale when add button is clicked', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: {'sulu_admin.add': {}},
            },
        },
    };

    const list = mount(<List router={router} />);
    const toolbarConfig = toolbarFunction.call(list.instance());

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toBeCalledWith('addRoute', {});
});

test('Should fire callback instead of navigate when onItemAdd prop is set and add button is clicked', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: {'sulu_admin.add': {}},
            },
        },
    };
    const itemAddCallback = jest.fn();

    const list = mount(<List onItemAdd={itemAddCallback} router={router} />);
    const toolbarConfig = toolbarFunction.call(list.instance());

    toolbarConfig.items[0].onClick();

    expect(itemAddCallback).toBeCalledWith(undefined);
    expect(router.navigate).not.toBeCalled();
});

test('Should navigate when pencil button is clicked and locales have been passed in options', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editRoute: 'editRoute',
                locales: ['de', 'en'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };
    list.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1, locale: 'de'});
});

test('Should navigate without locale when pencil button is clicked', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editRoute: 'editRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1});
});

test('Should fire callback instead of navigate when onItemClick prop is set and pencil button is clicked', () => {
    const onItemClickCallback = jest.fn();

    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editRoute: 'editRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List onItemClick={onItemClickCallback} router={router} />);
    list.find('ButtonCell button').at(0).simulate('click');

    expect(onItemClickCallback).toBeCalledWith(1);
    expect(router.navigate).not.toBeCalled();
});

test('Should load the route attributes from the ListStore', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(50);

    expect(List.getDerivedRouteAttributes({
        options: {
            listKey: 'list_test',
            resourceKey: 'test',
        },
    })).toEqual({
        active: 'some-uuid',
        limit: 50,
        sortColumn: 'title',
        sortOrder: 'desc',
    });

    expect(ListStore.getActiveSetting).toBeCalledWith('list_test', 'list');
    expect(ListStore.getSortColumnSetting).toBeCalledWith('list_test', 'list');
    expect(ListStore.getSortOrderSetting).toBeCalledWith('list_test', 'list');
    expect(ListStore.getLimitSetting).toBeCalledWith('list_test', 'list');
});

test('Should return the limit route attributes as undefined if ListStore is set to default value', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(10);

    expect(List.getDerivedRouteAttributes({
        options: {
            listKey: 'list_test',
            resourceKey: 'test',
        },
    })).toEqual({
        active: 'some-uuid',
        limit: undefined,
        sortColumn: 'title',
        sortOrder: 'desc',
    });

    expect(ListStore.getActiveSetting).toBeCalledWith('list_test', 'list');
    expect(ListStore.getSortColumnSetting).toBeCalledWith('list_test', 'list');
    expect(ListStore.getSortOrderSetting).toBeCalledWith('list_test', 'list');
    expect(ListStore.getLimitSetting).toBeCalledWith('list_test', 'list');
});

test('Should load the route attributes from the ListStore using the passed userSettingsKey', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(50);

    expect(List.getDerivedRouteAttributes({
        options: {
            listKey: 'list_test',
            resourceKey: 'test',
            userSettingsKey: 'user_key',
        },
    })).toEqual({
        active: 'some-uuid',
        limit: 50,
        sortColumn: 'title',
        sortOrder: 'desc',
    });

    expect(ListStore.getActiveSetting).toBeCalledWith('list_test', 'user_key');
    expect(ListStore.getSortColumnSetting).toBeCalledWith('list_test', 'user_key');
    expect(ListStore.getSortOrderSetting).toBeCalledWith('list_test', 'user_key');
    expect(ListStore.getLimitSetting).toBeCalledWith('list_test', 'user_key');
});

test('Should render the delete item enabled only if something is selected', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: {'sulu_admin.delete': {}},
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    let toolbarConfig, item;
    toolbarConfig = toolbarFunction.call(list.instance());
    item = toolbarConfig.items.find((item) => item.label === 'Delete');
    expect(item.disabled).toBe(true);

    listStore.selectionIds.push(1);
    toolbarConfig = toolbarFunction.call(list.instance());
    item = toolbarConfig.items.find((item) => item.label === 'Delete');
    expect(item.disabled).toBe(false);
});

test('Should render the locale dropdown with the options from router', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.locale.value).toBe('de');
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should pass apiOptions from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {
                    webspace: 'example',
                },
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.webspace).toEqual('example');
});

test('Should pass router attributes from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        attributes: {
            id: '123-123-123',
            locale: 'en',
            title: 'Sulu is awesome',
        },
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListStore: {'0': 'locale', 1: 'title', 'id': 'parentId'},
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.locale).toEqual('en');
    expect(listStore.options.parentId).toEqual('123-123-123');
    expect(listStore.options.title).toEqual('Sulu is awesome');
});

test('Should pass resourceStore properties from router to the ListStore', () => {
    const List = require('../List').default;
    const resourceStore = new ResourceStore('tests', '123-456-789');
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                resourceStorePropertiesToListStore: {'0': 'locale', 1: 'title', 'id': 'parentId'},
            },
        },
    };

    const list = mount(<List resourceStore={resourceStore} router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.locale).toEqual('de');
    expect(listStore.options.parentId).toEqual('123-456-789');
    expect(listStore.options.title).toEqual('Sulu rocks');
});

test('Should pass router attributes array from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        attributes: {
            id: '123-123-123',
            locale: 'en',
            title: 'Sulu is awesome',
        },
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListStore: observable(['locale', 'title', 'id']),
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.locale).toEqual('en');
    expect(listStore.options.id).toEqual('123-123-123');
    expect(listStore.options.title).toEqual('Sulu is awesome');
});

test('Should pass router attributes array from router to the ListStore metadataOptions', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        attributes: {
            id: '123-123-123',
            locale: 'en',
            title: 'Sulu is awesome',
        },
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListMetadata: ['locale', 'id'],
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.metadataOptions.locale).toEqual('en');
    expect(listStore.metadataOptions.id).toEqual('123-123-123');
    expect(listStore.metadataOptions.title).toBeUndefined();
});

test('Should pass locale and page observables to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).toHaveProperty('locale');
});

test('Should pass locale observable from props to the ListStore if it is set', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const locale = observable.box('ru');
    const list = mount(<List locale={locale} router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.observableOptions.locale).toEqual(locale);
});

test('Should not pass the locale observable to the ListStore if no locales are defined', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).not.toHaveProperty('locale');
});

test('Should fire reload method of ListStore when reload method is called', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const listInstance = mount(<List router={router} />).instance();
    listInstance.reload();

    expect(listInstance.listStore.reload).toBeCalled();
});

test('Should delete selected items when delete button is clicked', () => {
    function getDeleteItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Delete');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: {'sulu_admin.delete': {}},
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(false);

    getDeleteItem().onClick();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);
});

test('Should make move overlay disappear if cancel is clicked', () => {
    function getMoveItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Move selected');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: {'sulu_admin.move': {}},
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);

    getMoveItem().onClick();
    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(true);
    list.find('SingleListOverlay[title="Move items"]').prop('onClose')();

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);
});

test('Should move items after move overlay was confirmed', () => {
    function getMoveItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Move selected');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: {'sulu_admin.move': {}},
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    const moveSelectionPromise = Promise.resolve();
    listStore.moveSelection.mockReturnValue(moveSelectionPromise);

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);

    getMoveItem().onClick();
    listStore.movingSelection = true;
    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(true);
    list.find('SingleListOverlay[title="Move items"]').prop('onConfirm')({id: 5});

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('confirmLoading')).toEqual(true);

    expect(listStore.moveSelection).toBeCalledWith(5);

    return moveSelectionPromise.then(() => {
        listStore.movingSelection = false;
        list.update();
        expect(list.find('SingleListOverlay[title="Move items"]').prop('confirmLoading')).toEqual(false);
        expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);
    });
});

test('Export dialog should open when the button is pressed', () => {
    function getExportItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Export');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: {'sulu_admin.export': {}},
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();
    expect(list.find('Overlay').find({confirmText: 'Export'}).prop('open')).toEqual(false);

    getExportItem().onClick();
    list.update();

    expect(list.find('Overlay').find({confirmText: 'Export'}).prop('open')).toEqual(true);
});

test('Render export dialog', () => {
    function getExportItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Export');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: {'sulu_admin.export': {}},
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();

    getExportItem().onClick();
    list.update();

    expect(list.find('Overlay').find({confirmText: 'Export'}).render()).toMatchSnapshot();
});

test('Export method should be called when the export-button is pressed', () => {
    function getExportItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Export');
    }

    window.location.assign = jest.fn();

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const resourceRouteRegistry = require('../../../services/ResourceRequester/registries/ResourceRouteRegistry');
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: {'sulu_admin.export': {}},
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                locales: ['de', 'en'],
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);
    list.update();

    getExportItem().onClick();
    list.update();

    list.find('Overlay').find({confirmText: 'Export'}).find('Button').simulate('click');
    expect(resourceRouteRegistry.getListUrl).toBeCalledWith('test', {
        _format: 'csv',
        locale: list.instance().locale.get(),
        flat: true,
        delimiter: ';',
        escape: '\\',
        enclosure: '"',
        newLine: '\\n',
    });
    expect(window.location.assign).toBeCalledWith(
        'testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'
    );

    expect(list.find('Overlay').find({confirmText: 'Export'}).prop('open')).toEqual(false);
});
