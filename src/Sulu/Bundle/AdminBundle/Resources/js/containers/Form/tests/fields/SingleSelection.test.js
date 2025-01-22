// @flow
import React from 'react';
import log from 'loglevel';
import {mount, shallow} from 'enzyme';
import {observable, extendObservable as mockExtendObservable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import SingleSelectionStore from '../../../../stores/SingleSelectionStore';
import userStore from '../../../../stores/userStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelection from '../../fields/SingleSelection';
import SingleSelectionComponent from '../../../../containers/SingleSelection';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../containers/SingleListOverlay', () => jest.fn(() => null));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, locale) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = locale;
}));

jest.mock('../../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, selectedItemId, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: selectedItemId ? {id: selectedItemId} : undefined});
}));

jest.mock('../../../../stores/userStore', () => ({}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.options = options;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.getValueByPath = jest.fn();
    this.addFinishFieldHandler = jest.fn();
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass correct props and SingleSelectionStore to SingleAutoComplete container', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, locale),
            'test'
        )
    );

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="entity-id"
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        options: {},
        searchProperties: ['name', 'number'],
        selectionStore: singleSelection.instance().autoCompleteSelectionStore,
    }));

    expect(SingleSelectionStore).toBeCalledWith('accounts', 'entity-id', locale);
});

test('Pass correct options to SingleAutoComplete with deprecated data_path_to_auto_complete schema option', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        data_path_to_auto_complete: {
            name: 'data_path_to_auto_complete',
            value: [
                {name: 'id', value: 'accountId'},
            ],
        },
    };

    formInspector.getValueByPath.mockReturnValue(5);

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="entitiy-id"
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/id');
    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        options: {
            accountId: 5,
        },
    }));
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "data_path_to_auto_complete" option is deprecated'));
});

test('Use locale from userStore and pass correct props with schema-options type to SingleAutoComplete', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, undefined),
            'test'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'en';

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        type: {
            name: 'type',
            value: 'auto_complete',
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="entity-id"
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        options: {},
        searchProperties: ['name', 'number'],
        selectionStore: singleSelection.instance().autoCompleteSelectionStore,
    }));

    expect(singleSelection.find('SingleAutoComplete').props().selectionStore.resourceKey).toEqual('accounts');
    expect(singleSelection.find('SingleAutoComplete').props().selectionStore.item).toEqual({id: 'entity-id'});
    expect(singleSelection.find('SingleAutoComplete').props().selectionStore.locale.get()).toEqual('en');
});

test('Call onChange and onFinish when item of auto_complete SingleSelectionStore changes', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, undefined),
            'test'
        )
    );
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="entity-id"
        />
    );

    singleSelection.instance().autoCompleteSelectionStore.item = {id: 'new-entity-id'};

    expect(changeSpy).toBeCalledWith('new-entity-id');
    expect(finishSpy).toBeCalledWith();
});

test('Handle object without warning when "use_deprecated_object_data_format" option of auto_complete is set', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, undefined),
            'test'
        )
    );
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        use_deprecated_object_data_format: {name: 'use_deprecated_object_data_format', value: true},
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={({id: 'old-entity-id'}: any)}
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props().selectionStore.item).toEqual({id: 'old-entity-id'});
    expect(log.warn).toBeCalledWith(expect.stringContaining('"use_deprecated_object_data_format" param is deprecated'));
    expect(log.warn).not.toBeCalledWith(expect.stringContaining('expects an id as value but received an object'));

    singleSelection.instance().autoCompleteSelectionStore.item = {id: 'new-entity-id'};

    expect(changeSpy).toBeCalledWith({id: 'new-entity-id'});
    expect(finishSpy).toBeCalledWith();
});

test('Throw an error if the auto_complete configuration was omitted', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {},
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"auto_complete"/);
});

test('Pass correct props to SingleSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {
                display_property: 'name',
                id_property: 'id',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{editable: {name: 'editable', value: true}}}
            value={value}
        />
    );

    expect(singleSelection.find('ResourceSingleSelect').props()).toEqual(expect.objectContaining({
        displayProperty: 'name',
        editable: true,
        idProperty: 'id',
        overlayTitle: 'sulu_contact.overlay_title',
    }));
});

test('Call onChange and onFinish when SingleSelect changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {
                display_property: 'name',
                id_property: 'id',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    singleSelection.find('ResourceSingleSelect').simulate('change', 2);

    expect(changeSpy).toBeCalledWith(2);
    expect(finishSpy).toBeCalledWith();
});

test('Throw an error if no display_property is passed to the the single_select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {},
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"display_property"/);
});

test('Throw an error if no id_property is passed to the the single_select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {
                display_property: 'something',
            },
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"id_property"/);
});

test('Pass correct props to SingleItemSelection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                list_key: 'accounts_list',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: true,
        listKey: 'accounts_list',
        detailOptions: {},
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        itemDisabledCondition: undefined,
        listOptions: {},
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

test('Pass resourceKey as listKey to SingleItemSelection if no listKey is given', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).prop('listKey')).toEqual('accounts');
});

test('Pass null as value to SingleSelection for list_overlay', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(singleSelection.find('SingleSelection').prop('value')).toEqual(null);
});

test('Should log warning and use id of object if given value is an object instead of an id', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={({id: 125}: any)}
        />
    );

    expect(singleSelection.find('SingleSelection').prop('value')).toEqual(125);
    expect(log.warn).toBeCalledWith(expect.stringContaining('expects an id as value but received an object'));
});

test('Throw an error if form_options_to_list_options schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        form_options_to_list_options: {
            name: 'form_options_to_api',
            value: 'test',
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"form_options_to_list_options"');
});

test('Throw an error if request_parameters schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        request_parameters: {
            name: 'request_parameters',
            value: 'test',
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"request_parameters"');
});

test('Should throw an error if "resource_store_properties_to_request" schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {},
        },
    };

    const schemaOptions = {
        resource_store_properties_to_request: {name: 'resource_store_properties_to_request', value: 'not-an-array'},
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow('"resource_store_properties_to_request"');
});

test('Should throw an error if "form_options_to_request" schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {},
        },
    };

    const schemaOptions = {
        form_options_to_request: {name: 'form_options_to_request', value: 'not-an-array'},
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow('"form_options_to_request"');
});

test('Throw an error if item_disabled_condition schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        item_disabled_condition: {
            name: 'item_disabled_condition',
            value: [],
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"item_disabled_condition"');
});

test('Throw an error if allow_deselect_for_disabled_items schema option is not a boolean', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        allow_deselect_for_disabled_items: {
            name: 'allow_deselect_for_disabled_items',
            value: 'not-boolean',
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"allow_deselect_for_disabled_items"');
});

test('Throw an error if detail_options has wrong value', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                detail_options: 'test',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    )).toThrow('"detail_options"');
});

test('Pass correct props with schema-options type to SingleItemSelection', () => {
    const options = {
        segment: 'developer',
        webspace: 'sulu',
        'otherOptionKey': 'value-from-form-options',
    };

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                detail_options: {
                    'ghost-content': true,
                },
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        allow_deselect_for_disabled_items: {
            name: 'allow_deselect_for_disabled_items',
            value: false,
        },
        form_options_to_list_options: {
            name: 'form_options_to_list_options',
            value: [
                {name: 'segment'},
                {name: 'webspace'},
            ],
        },
        type: {
            name: 'type',
            value: 'list_overlay',
        },
        item_disabled_condition: {
            name: 'item_disabled_condition',
            value: 'status == "inactive"',
        },
        types: {
            name: 'types',
            value: 'test',
        },
        request_parameters: {
            name: 'request_parameters',
            value: [
                {
                    name: 'rootKey',
                    value: 'testRootKey',
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test', options));

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/otherPropertyName');

    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: false,
        detailOptions: {
            'ghost-content': true,
            rootKey: 'testRootKey',
            dynamicKey: 'value-returned-by-form-inspector',
            optionKey: 'value-from-form-options',
        },
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        itemDisabledCondition: 'status == "inactive"',
        listOptions: {
            segment: 'developer',
            webspace: 'sulu',
            types: 'test',
            rootKey: 'testRootKey',
            dynamicKey: 'value-returned-by-form-inspector',
            optionKey: 'value-from-form-options',
        },
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

// eslint-disable-next-line max-len
test('Should update props of SingleItemSelection when value of "resource_store_properties_to_request" property is changed', () => {
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test', {}));

    const formInspectorValues = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.addFinishFieldHandler).toHaveBeenCalled();
    expect(singleSelection.find(SingleSelectionComponent).props().detailOptions).toEqual({
        dynamicKey: 'first-value',
    });
    expect(singleSelection.find(SingleSelectionComponent).props().listOptions).toEqual({
        dynamicKey: 'first-value',
    });

    formInspectorValues['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];
    finishFieldHandler('/otherPropertyName');

    expect(singleSelection.find(SingleSelectionComponent).props().detailOptions).toEqual({
        dynamicKey: 'second-value',
    });
    expect(singleSelection.find(SingleSelectionComponent).props().listOptions).toEqual({
        dynamicKey: 'second-value',
    });
});

test('Should keep form options when value of "resource_store_properties_to_request" property is changed', () => {
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
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

    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'),
            'test',
            {'otherOptionKey': 'value-from-form-options'}
        )
    );

    const formInspectorValues = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.addFinishFieldHandler).toHaveBeenCalled();
    expect(singleSelection.find(SingleSelectionComponent).props().detailOptions).toEqual({
        dynamicKey: 'first-value',
        optionKey: 'value-from-form-options',
    });
    expect(singleSelection.find(SingleSelectionComponent).props().listOptions).toEqual({
        dynamicKey: 'first-value',
        optionKey: 'value-from-form-options',
    });

    formInspectorValues['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];
    finishFieldHandler('/otherPropertyName');

    expect(singleSelection.find(SingleSelectionComponent).props().detailOptions).toEqual({
        dynamicKey: 'second-value',
        optionKey: 'value-from-form-options',
    });
    expect(singleSelection.find(SingleSelectionComponent).props().listOptions).toEqual({
        dynamicKey: 'second-value',
        optionKey: 'value-from-form-options',
    });
});

test('Throw an error if "type" schema-options is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        type: {
            name: 'type',
            value: true,
        },
    };

    expect(
        () => shallow(
            <SingleSelection
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

test('Throw an error if "default_type" field-type-option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: true,
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                disabled={true}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
                value={value}
            />
        )
    ).toThrow(/"default_type"/);
});

test('Pass content locale from user to SingleItemSelection if form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('accounts', 5), 'test'));

    // $FlowFixMe
    userStore.contentLocale = 'en';

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).prop('locale').get()).toEqual('en');
});

test('Pass correct locale and disabledIds to SingleItemSelection', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('accounts', 5, locale), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
        disabledIds: [5],
        locale,
    }));
});

test('Call onChange and onFinish when SingleSelection changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    singleSelection.find(SingleSelectionComponent).simulate('change', undefined);

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});

test('Should not fail when SingleItemSelection item is clicked without configured view', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const router = new Router();

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    // $FlowFixMe
    SingleSelectionStore.mockImplementation(function() {
        this.item = {id: 6};
    });

    const singleSelection = mount(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            router={router}
            value={value}
        />
    );

    singleSelection.find('SingleItemSelection .item').simulate('click');

    expect(router.navigate).not.toBeCalled();
});

test('Navigate when SingleItemSelection item is clicked with configured view', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const router = new Router();

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        view: {
            name: 'sulu_contact.account_edit_form',
            result_to_view: {
                id: 'id',
            },
        },
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    // $FlowFixMe
    SingleSelectionStore.mockImplementation(function() {
        this.item = {id: 6};
    });

    const singleSelection = mount(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            router={router}
            value={value}
        />
    );

    singleSelection.find('SingleItemSelection .item').simulate('click');

    expect(router.navigate).toBeCalledWith('sulu_contact.account_edit_form', {id: 6});
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
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: []}}}
        />
    )).toThrowError(/"types"/);
});

test('Should throw an error if no "resource_key" option is passed in fieldOptions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{default_type: 'list_overlay'}}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: []}}}
        />
    )).toThrowError(/"resource_key"/);
});

test('Should pass request_parameters to auto_complete options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        request_parameters: {
            name: 'request_parameters',
            type: 'collection',
            value: [
                {
                    name: 'ids',
                    value: 1,
                },
            ],
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        options: {
            ids: 1,
        },
    }));
});

test('Should pass request_parameters and dataPathToAutoComplete to auto_complete options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        data_path_to_auto_complete: {
            name: 'data_path_to_auto_complete',
            value: [
                {name: 'id', value: 'accountId'},
            ],
        },
        request_parameters: {
            name: 'request_parameters',
            type: 'collection',
            value: [
                {
                    name: 'ids',
                    value: 1,
                },
            ],
        },
    };

    formInspector.getValueByPath.mockReturnValue(5);

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        options: {
            ids: 1,
            accountId: 5,
        },
    }));
});

test('Should pass same request_parameters and dataPathToAutoComplete options to auto_complete options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        data_path_to_auto_complete: {
            name: 'data_path_to_auto_complete',
            value: [
                {name: 'id', value: 'accountId'},
            ],
        },
        request_parameters: {
            name: 'request_parameters',
            type: 'collection',
            value: [
                {
                    name: 'accountId',
                    value: 1,
                },
            ],
        },
    };

    formInspector.getValueByPath.mockReturnValue(5);

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        options: {
            accountId: 1,
        },
    }));
});
