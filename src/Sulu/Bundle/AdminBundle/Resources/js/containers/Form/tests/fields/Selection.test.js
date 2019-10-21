// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable, toJS} from 'mobx';
import {mount, shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {translate} from '../../../../utils/Translator';
import ResourceStore from '../../../../stores/ResourceStore';
import userStore from '../../../../stores/userStore';
import List from '../../../List';
import Selection from '../../fields/Selection';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';

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
        this.locale = observableOptions.locale;
        this.initialSelectionIds = initialSelectionIds;
        this.dataLoading = true;
        this.destroy = jest.fn();
        this.sendRequestDisposer = jest.fn();

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
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.locale = resourceStore.locale;
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, options) {
    this.id = id;
    this.resourceKey = resourceKey;
    this.locale = options ? options.locale : undefined;
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should pass props correctly to selection component', () => {
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
        listKey: 'snippets_list',
        disabled: true,
        displayProperties: ['id', 'title'],
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

test('Should pass locale from userStore to selection component if form has no locale', () => {
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

test('Should pass props with schema-options type correctly to selection component', () => {
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
        disabled: true,
        displayProperties: ['id', 'title'],
        label: 'sulu_snippet.selection_label',
        locale,
        resourceKey: 'snippets',
        overlayTitle: 'sulu_snippet.selection_overlay_title',
        value,
    }));
});

test('Should pass id of form as disabledId to overlay type to avoid assigning something to itself', () => {
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

test('Should pass empty array if value is not given to overlay type', () => {
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

test('Should call onChange and onFinish callback when selection overlay is confirmed', () => {
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
            schemaOptions={{types: {value: []}}}
        />
    )).toThrowError(/"types"/);
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
    selection.instance().changeListDisposer = changeListDisposerSpy;
    selection.instance().changeLocaleDisposer = changeLocaleDisposerSpy;
    const listStoreDestroy = selection.instance().listStore.destroy;

    selection.unmount();

    expect(changeListDisposerSpy).toBeCalledWith();
    expect(changeLocaleDisposerSpy).toBeCalledWith();
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

test('Should pass props correctly to list component', () => {
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

    expect(selection.instance().listStore.listKey).toEqual('snippets_list');
    expect(selection.instance().listStore.resourceKey).toEqual('snippets');
    expect(selection.instance().listStore.initialSelectionIds).toEqual(value);
    expect(selection.find(List).props()).toEqual(expect.objectContaining({
        adapters: ['table'],
        disabled: true,
        searchable: false,
    }));
});

test('Should pass resourceKey as listKey to list component if no listKey is given', () => {
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

    expect(selection.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        allowAdd: false,
        disabled: true,
        displayProperty: 'name',
        filterParameter: 'names',
        idProperty: 'uuid',
        locale,
        resourceKey: 'snippets',
        searchProperties: ['name'],
        value,
    }));
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

    expect(toJS(selection.find('MultiAutoComplete').prop('locale'))).toEqual('de');
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
            'pages'
        )
    );

    const schemaOptions = {
        type: {
            name: 'type',
            value: 'auto_complete',
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

    expect(selection.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        allowAdd: false,
        disabled: true,
        displayProperty: 'name',
        filterParameter: 'names',
        idProperty: 'uuid',
        locale,
        resourceKey: 'snippets',
        searchProperties: ['name'],
        value,
    }));
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
