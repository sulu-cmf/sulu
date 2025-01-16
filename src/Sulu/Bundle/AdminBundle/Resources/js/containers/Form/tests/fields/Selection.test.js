// @flow
import React from 'react';
import log from 'loglevel';
import {extendObservable as mockExtendObservable, observable, toJS} from 'mobx';
import {mount, shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {translate} from '../../../../utils/Translator';
import MultiSelectionStore from '../../../../stores/MultiSelectionStore';
import ResourceStore from '../../../../stores/ResourceStore';
import userStore from '../../../../stores/userStore';
import Router from '../../../../services/Router';
import List from '../../../List';
import Selection from '../../fields/Selection';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(
    function(resourceKey, selectedItemIds, locale, idFilterParameter) {
        this.locale = locale;
        this.loading = false;
        this.idFilterParameter = idFilterParameter;
        this.loadItems = jest.fn();

        mockExtendObservable(this, {
            items: [],
            ids: [],
        });
    })
);

jest.mock('../../../../services/Router', () => jest.fn(() => ({
    navigate: jest.fn(),
})));

jest.mock('../../../List', () => jest.fn(() => null));

jest.mock('../../../List/stores/ListStore',
    () => function(
        resourceKey,
        listKey,
        userSettingsKey,
        observableOptions = {},
        options,
        metadataOptions,
        initialSelectionIds
    ) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.observableOptions = observableOptions;
        this.locale = observableOptions.locale;
        this.initialSelectionIds = initialSelectionIds;
        this.dataLoading = true;
        this.destroy = jest.fn();
        this.sendRequestDisposer = jest.fn();
        this.reset = jest.fn();
        this.clearSelection = jest.fn();
        this.select = jest.fn();

        mockExtendObservable(this, {
            selectionIds: [],
        });
    }
);

jest.mock('../../../../stores/userStore', () => ({}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.resourceKey = formStore.resourceKey;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.getValueByPath = jest.fn();
    this.addFinishFieldHandler = jest.fn();
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.locale = resourceStore.locale;
    this.options = options;
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, options) {
    this.id = id;
    this.resourceKey = resourceKey;
    this.locale = options ? options.locale : undefined;
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should pass props correctly to MultiSelection component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                list_key: 'snippets_list',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const schemaOptions = {
        types: {
            name: 'types',
            value: 'test',
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(translate).toBeCalledWith('sulu_snippet.selection_label', {count: 3});

    expect(selection.find('MultiSelection').props()).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: true,
        listKey: 'snippets_list',
        disabled: true,
        sortable: true,
        displayProperties: ['id', 'title'],
        itemDisabledCondition: undefined,
        label: 'sulu_snippet.selection_label',
        locale,
        resourceKey: 'snippets',
        options: {types: 'test'},
        overlayTitle: 'sulu_snippet.selection_overlay_title',
        value,
    }));
});

test('Should pass resourceKey as listKey to selection component if no listKey is given', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(selection.find('MultiSelection').prop('listKey')).toEqual('snippets');
});

test('Should pass locale from userStore to MultiSelection component if form has no locale', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'de';

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(translate).toBeCalledWith('sulu_snippet.selection_label', {count: 3});

    expect(toJS(selection.find('MultiSelection').prop('locale'))).toEqual('de');
});

test('Should pass props with schema-options correctly to MultiSelection component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
            auto_complete: {
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const schemaOptions = {
        type: {
            name: 'type',
            value: 'list_overlay',
        },
        types: {
            name: 'types',
            value: 'image,video',
        },
        allow_deselect_for_disabled_items: {
            name: 'allow_deselect_for_disabled_items',
            value: false,
        },
        item_disabled_condition: {
            name: 'item_disabled_condition',
            value: 'status == "inactive"',
        },
        sortable: {
            name: 'sortable',
            value: false,
        },
        request_parameters: {
            name: 'request_parameters',
            value: [
                {
                    name: 'staticKey',
                    value: 'some-static-value',
                },
            ],
        },
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
        form_options_to_request: {
            name: 'form_options_to_request',
            value: [
                {
                    name: 'optionKey',
                    value: 'otherOptionKey',
                },
            ],
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages',
            {'otherOptionKey': 'value-from-form-options'}
        )
    );

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(translate).toBeCalledWith('sulu_snippet.selection_label', {count: 3});
    expect(formInspector.getValueByPath).toBeCalledWith('/otherPropertyName');

    expect(selection.find('MultiSelection').props()).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: false,
        disabled: true,
        sortable: false,
        displayProperties: ['id', 'title'],
        itemDisabledCondition: 'status == "inactive"',
        label: 'sulu_snippet.selection_label',
        locale,
        resourceKey: 'snippets',
        overlayTitle: 'sulu_snippet.selection_overlay_title',
        value,
        options: {
            types: 'image,video',
            staticKey: 'some-static-value',
            dynamicKey: 'value-returned-by-form-inspector',
            optionKey: 'value-from-form-options',
        },
    }));
});

// eslint-disable-next-line max-len
test('Should update props of MultiSelection component when value of "resource_store_properties_to_request" property is changed', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const schemaOptions = {
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const formInspectorValues = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.addFinishFieldHandler).toHaveBeenCalled();
    expect(selection.find('MultiSelection').props().options).toEqual({
        dynamicKey: 'first-value',
    });

    formInspectorValues['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];
    finishFieldHandler('/otherPropertyName');

    expect(selection.find('MultiSelection').props().options).toEqual({
        dynamicKey: 'second-value',
    });
});

test('Should pass id of form as disabledId to MultiSelection component to avoid assigning something to itself', () => {
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'pages',
        types: {
            list_overlay: {
                adapter: 'table',
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('pages', 4), 'pages'));

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(selection.find('MultiSelection').prop('disabledIds')).toEqual([4]);
});

test('Should pass empty array to MultiSelection component if value is not given', () => {
    const changeSpy = jest.fn();
    const fieldOptions = {
        default_type: 'list_overlay',
        resource_key: 'pages',
        types: {
            list_overlay: {
                adapter: 'column_list',
                label: 'sulu_page.selection_label',
            },
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
        />
    );

    expect(translate).toBeCalledWith('sulu_page.selection_label', {count: 0});
    expect(selection.find('MultiSelection').props()).toEqual(expect.objectContaining({
        adapter: 'column_list',
        resourceKey: 'pages',
        value: [],
    }));
});

test('Should call onChange and onFinish callback when MultiSelection component fires onChange callback', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldOptions = {
        default_type: 'list_overlay',
        resource_key: 'pages',
        types: {
            list_overlay: {
                adapter: 'column_list',
                label: 'sulu_page.selection_label',
            },
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.find('MultiSelection').prop('onChange')([1, 2, 3]);

    expect(changeSpy).toBeCalledWith([1, 2, 3]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not fail when MultiSelection item is clicked without configured view', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldOptions = {
        default_type: 'list_overlay',
        resource_key: 'pages',
        types: {
            list_overlay: {
                adapter: 'column_list',
                label: 'sulu_page.selection_label',
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    const router = new Router();

    const selection = mount(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            router={router}
            value={[1, 2]}
        />
    );

    selection.find('MultiSelection').instance().selectionStore.items = [
        {id: 1, locale: 'de', title: 'Test'},
        {id: 2, locale: 'de', title: 'Impressum'},
    ];

    selection.update();

    expect(selection.find('MultiSelection').prop('onItemClick')).toEqual(undefined);

    expect(selection.find('MultiItemSelection Item .content').at(0).prop('onClick')).toEqual(undefined);
    expect(selection.find('MultiItemSelection Item .content').at(0).prop('role')).toEqual(undefined);
    expect(selection.find('MultiItemSelection Item .content').at(1).prop('onClick')).toEqual(undefined);
    expect(selection.find('MultiItemSelection Item .content').at(1).prop('role')).toEqual(undefined);
    expect(router.navigate).not.toBeCalled();
});

test('Should navigate to view when MultiSelection item is clicked with configured view', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldOptions = {
        default_type: 'list_overlay',
        resource_key: 'pages',
        view: {
            name: 'sulu_page.page_edit_form',
            result_to_view: {
                'properties/locale': 'locale',
                id: 'uuid',
            },
        },
        types: {
            list_overlay: {
                adapter: 'column_list',
                label: 'sulu_page.selection_label',
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    const router = new Router();

    const selection = mount(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            router={router}
            value={[1, 2]}
        />
    );

    selection.find('MultiSelection').instance().selectionStore.items = [
        {id: 1, properties: {locale: 'de', title: 'Test'}},
        {id: 2, properties: {locale: 'de', title: 'Impressum'}},
    ];

    selection.update();

    selection.find('MultiItemSelection Item .content').at(0).prop('onClick')();
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {locale: 'de', uuid: 1});
    selection.find('MultiItemSelection Item .content').at(1).prop('onClick')();
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {locale: 'de', uuid: 2});
});

test('Should log warning and use ids of objects if given value is an array of objects', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {
                adapter: 'table',
            },
        },
    };

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={([{id: 55}, {id: 66}]: any)}
        />
    );

    expect(selection.find('MultiSelection').prop('value')).toEqual([55, 66]);
    expect(log.warn).toBeCalledWith(expect.stringContaining('expects an array of ids as value'));
});

test('Should throw an error if "types" schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'type', value: []}}}
        />
    )).toThrowError(/"types"/);
});

test('Should throw an error if "item_disabled_condition" schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{item_disabled_condition: {name: 'item_disabled_condition', value: []}}}
        />
    )).toThrowError(/"item_disabled_condition"/);
});

test('Should throw an error if "allow_deselect_for_disabled_items" schema option is not a boolean', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };
    const schemaOptions = {
        allow_deselect_for_disabled_items: {
            name: 'allow_deselect_for_disabled_items',
            value: 'not-boolean',
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrowError(/"allow_deselect_for_disabled_items"/);
});

test('Should throw an error if "sortable" schema option is not a boolean', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };
    const schemaOptions = {
        sortable: {
            name: 'sortable',
            value: 'not-boolean',
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrowError(/"sortable"/);
});

test('Should throw an error if "request_parameters" schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{request_parameters: {name: 'request_parameters', value: 'not-an-array'}}}
        />
    )).toThrowError(/"request_parameters"/);
});

test('Should throw an error if "resource_store_properties_to_request" schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };
    const schemaOptions = {
        resource_store_properties_to_request: {name: 'resource_store_properties_to_request', value: 'not-an-array'},
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrowError(/"resource_store_properties_to_request"/);
});

test('Should throw an error if "form_options_to_request" schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };
    const schemaOptions = {
        form_options_to_request: {name: 'form_options_to_request', value: 'not-an-array'},
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrowError(/"form_options_to_request"/);
});

test('Should throw an error if no "resource_key" option is passed in fieldOptions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{default_type: 'list_overlay'}}
            formInspector={formInspector}
        />
    )).toThrowError(/"resource_key"/);
});

test('Should throw an error if no "adapter" option is passed for overlay type in fieldTypeOptions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    )).toThrowError(/"adapter"/);
});

test('Should call the disposers for list selections and locale and ListStore if unmounted', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'test',
        types: {
            list: {
                adapter: 'tree_table',
            },
        },
    };

    const selection = mount(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    const changeListDisposerSpy = jest.fn();
    const changeLocaleDisposerSpy = jest.fn();
    const changeListOptionsDisposerSpy = jest.fn();
    const changeAutoCompleteSelectionDisposerSpy = jest.fn();
    selection.instance().changeListDisposer = changeListDisposerSpy;
    selection.instance().changeLocaleDisposer = changeLocaleDisposerSpy;
    selection.instance().changeListOptionsDisposer = changeListOptionsDisposerSpy;
    selection.instance().changeAutoCompleteSelectionDisposer = changeAutoCompleteSelectionDisposerSpy;
    const listStoreDestroy = selection.instance().listStore.destroy;

    selection.unmount();

    expect(changeListDisposerSpy).toBeCalledWith();
    expect(changeLocaleDisposerSpy).toBeCalledWith();
    expect(changeListOptionsDisposerSpy).toBeCalledWith();
    expect(changeAutoCompleteSelectionDisposerSpy).toBeCalledWith();
    expect(listStoreDestroy).toBeCalledWith();
});

test('Should call sendRequestDisposer to avoid extra request when locale is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    locale.set('de');

    expect(selection.instance().listStore.sendRequestDisposer).toBeCalledWith();
});

test('Should pass correct props to list component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
                list_key: 'snippets_list',
            },
        },
    };

    const schemaOptions = {
        item_disabled_condition: {
            name: 'item_disabled_condition',
            value: 'status == "inactive"',
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(selection.find(List).props()).toEqual(expect.objectContaining({
        adapters: ['table'],
        disabled: true,
        itemDisabledCondition: 'status == "inactive"',
        searchable: false,
        showColumnOptions: false,
    }));
});

test('Should pass correct parameters to listStore', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
                list_key: 'snippets_list',
            },
        },
    };

    const schemaOptions = {
        request_parameters: {
            name: 'request_parameters',
            value: [
                {
                    name: 'staticKey',
                    value: 'some-static-value',
                },
            ],
        },
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
        form_options_to_request: {
            name: 'form_options_to_request',
            value: [
                {
                    name: 'optionKey',
                    value: 'otherOptionKey',
                },
            ],
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages',
            {'otherOptionKey': 'value-from-form-options'}
        )
    );

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(selection.instance().listStore.resourceKey).toEqual('snippets');
    expect(selection.instance().listStore.listKey).toEqual('snippets_list');
    expect(selection.instance().listStore.userSettingsKey).toEqual('selection');
    expect(selection.instance().listStore.initialSelectionIds).toEqual(value);
    expect(selection.instance().listStore.options).toEqual({
        staticKey: 'some-static-value',
        dynamicKey: 'value-returned-by-form-inspector',
        optionKey: 'value-from-form-options',
    });
});

test('Should pass resourceKey as listKey to listStore if no listKey is given', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(selection.instance().listStore.listKey).toEqual('snippets');
});

test('Should pass locale from userStore to listStore if form has no locale', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
            },
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'en';

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(toJS(selection.instance().listStore.locale)).toEqual('en');
});

test('Should call onChange and onFinish prop when list selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().listStore.dataLoading = false;
    selection.instance().listStore.selectionIds = [1, 5, 7];

    expect(changeSpy).toBeCalledWith([1, 5, 7]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not call onChange and onFinish prop while list is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().listStore.selectionIds = [1, 5, 7];

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should update listStore when the value of a "resource_store_properties_to_request" property is changed', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
                list_key: 'snippets_list',
            },
        },
    };

    const schemaOptions = {
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const formInspectorValues = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.addFinishFieldHandler).toHaveBeenCalled();
    expect(selection.instance().listStore.options).toEqual({
        dynamicKey: 'first-value',
    });

    selection.instance().listStore.selectionIds = [12, 14];
    formInspectorValues['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];
    finishFieldHandler('/otherPropertyName');

    expect(selection.instance().listStore.options).toEqual({
        dynamicKey: 'second-value',
    });
    expect(selection.instance().listStore.reset).toBeCalled();
    expect(selection.instance().listStore.initialSelectionIds).toEqual([12, 14]);
});

// eslint-disable-next-line max-len
test('Should keep form options to request when the value of a "resource_store_properties_to_request" property is changed', () => {
    const value = [1, 6, 8];
    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
                list_key: 'snippets_list',
            },
        },
    };
    const schemaOptions = {
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
        form_options_to_request: {
            name: 'form_options_to_request',
            value: [
                {
                    name: 'optionKey',
                    value: 'otherOptionKey',
                },
            ],
        },
    };
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages',
            {'otherOptionKey': 'value-from-form-options'}
        )
    );
    const formInspectorValues = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );
    expect(formInspector.addFinishFieldHandler).toHaveBeenCalled();
    expect(selection.instance().listStore.options).toEqual({
        dynamicKey: 'first-value',
        optionKey: 'value-from-form-options',
    });
    selection.instance().listStore.selectionIds = [12, 14];
    formInspectorValues['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];
    finishFieldHandler('/otherPropertyName');
    expect(selection.instance().listStore.options).toEqual({
        dynamicKey: 'second-value',
        optionKey: 'value-from-form-options',
    });
    expect(selection.instance().listStore.reset).toBeCalled();
    expect(selection.instance().listStore.initialSelectionIds).toEqual([12, 14]);
});

test('Should not call onChange and onFinish if an observable that is accessed in one of the callbacks changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });
    const finishSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    const fieldTypeOptions = {
        default_type: 'list',
        resource_key: 'snippets',
        types: {
            list: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().listStore.dataLoading = false;

    // callbacks should be called when selection of list store changes
    selection.instance().listStore.selectionIds = [1, 5, 7];
    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(finishSpy).toHaveBeenCalledTimes(1);

    // callbacks should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(finishSpy).toHaveBeenCalledTimes(1);
});

test('Should pass props correctly to MultiAutoComplete component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'snippets',
        types: {
            auto_complete: {
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(selection.find('MultiAutoComplete').at(0).props()).toEqual(expect.objectContaining({
        allowAdd: false,
        disabled: true,
        displayProperty: 'name',
        idProperty: 'uuid',
        searchProperties: ['name'],
        selectionStore: selection.instance().autoCompleteSelectionStore,
    }));

    expect(MultiSelectionStore).toBeCalledWith('snippets', value, locale, 'names');
});

test('Should pass locale from userStore to MultiAutoComplete component if form has no locale', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'snippets',
        types: {
            auto_complete: {
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'de';

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(selection.instance().autoCompleteSelectionStore.locale.get()).toEqual('de');
});

test('Should pass props with schema-options type correctly to MultiAutoComplete component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
            auto_complete: {
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages',
            {'otherOptionKey': 'value-from-form-options'}
        )
    );

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const schemaOptions = {
        type: {
            name: 'type',
            value: 'auto_complete',
        },
        request_parameters: {
            name: 'request_parameters',
            value: [
                {
                    name: 'staticKey',
                    value: 'some-static-value',
                },
            ],
        },
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
        form_options_to_request: {
            name: 'form_options_to_request',
            value: [
                {
                    name: 'optionKey',
                    value: 'otherOptionKey',
                },
            ],
        },
    };

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/otherPropertyName');

    expect(selection.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        allowAdd: false,
        disabled: true,
        displayProperty: 'name',
        idProperty: 'uuid',
        searchProperties: ['name'],
        selectionStore: selection.instance().autoCompleteSelectionStore,
        options: {
            staticKey: 'some-static-value',
            dynamicKey: 'value-returned-by-form-inspector',
            optionKey: 'value-from-form-options',
        },
    }));
});

test('Should trigger a reload of the auto_complete items if the value prop changes', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'snippets',
        types: {
            auto_complete: {
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'de';

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(selection.instance().autoCompleteSelectionStore.loadItems).not.toBeCalled();

    selection.instance().autoCompleteSelectionStore.items = [{uuid: 1}, {uuid: 6}, {uuid: 8}];

    selection.setProps({value: [3, 4, 7]});

    expect(selection.instance().autoCompleteSelectionStore.loadItems).toBeCalledWith([3, 4, 7]);
});

test('Should not trigger a reload of the auto_complete items if the value prop changes to the same value again', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'snippets',
        types: {
            auto_complete: {
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'de';

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    selection.instance().autoCompleteSelectionStore.items = [{uuid: 1}, {uuid: 6}, {uuid: 8}];

    selection.setProps({value: [1, 6, 8]});

    expect(selection.instance().autoCompleteSelectionStore.loadItems).not.toBeCalled();
});

test('Throw an error if a none string was passed to schema-options', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const schemaOptions = {
        type: {
            name: 'type',
            value: true,
        },
    };

    expect(
        () => shallow(
            <Selection
                {...fieldTypeDefaultProps}
                disabled={true}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
                value={value}
            />
        )
    ).toThrow(/"type"/);
});

test('Throw an error if a none string was passed to field-type-options', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: true,
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    expect(
        () => shallow(
            <Selection
                {...fieldTypeDefaultProps}
                disabled={true}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
                value={value}
            />
        )
    ).toThrow(/"default_type"/);
});

test('Should call onChange and onFinish callback when content of selectionStore has changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldOptions = {
        default_type: 'auto_complete',
        resource_key: 'pages',
        types: {
            auto_complete: {
                allow_add: true,
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    const selection = mount(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().autoCompleteSelectionStore.dataLoading = false;
    selection.instance().autoCompleteSelectionStore.items = [
        {uuid: 1},
        {uuid: 2},
        {uuid: 3},
    ];

    expect(changeSpy).toBeCalledWith([1, 2, 3]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not call onChange and onFinish callback when content of selectionStore is empty and undefined', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldOptions = {
        default_type: 'auto_complete',
        resource_key: 'pages',
        types: {
            auto_complete: {
                allow_add: true,
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    const selection = mount(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().autoCompleteSelectionStore.dataLoading = false;
    selection.instance().autoCompleteSelectionStore.items = [];

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should pass allowAdd prop to MultiAutoComplete component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'snippets',
        types: {
            auto_complete: {
                allow_add: true,
                display_property: 'name',
                filter_parameter: 'names',
                id_property: 'uuid',
                search_properties: ['name'],
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1, {locale}),
            'pages'
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(selection.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        allowAdd: true,
    }));
});
