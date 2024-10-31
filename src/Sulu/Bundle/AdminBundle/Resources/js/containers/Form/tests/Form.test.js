// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import log from 'loglevel';
import Form from '../Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../stores/ResourceFormStore';
import metadataStore from '../stores/metadataStore';

const FORM = {
    locale: {
        label: 'Sprache wählen',
        disabledCondition: null,
        visibleCondition: null,
        description: '',
        type: 'single_select',
        colSpan: 6,
        options: {
            default_value: {
                name: 'default_value',
                type: null,
                value: 'de',
                title: null,
                placeholder: null,
                infoText: null,
            },
            values: {
                name: 'values',
                type: 'collection',
                value: [
                    {
                        name: 'de',
                        type: null,
                        value: 'de',
                        title: 'de',
                        placeholder: null,
                        infoText: null,
                    },
                    {
                        name: 'en',
                        type: null,
                        value: 'en',
                        title: 'en',
                        placeholder: null,
                        infoText: null,
                    },
                ],
                title: null,
                placeholder: null,
                infoText: null,
            },
        },
        types: [],
        defaultType: null,
        required: true,
        spaceAfter: null,
        minOccurs: null,
        maxOccurs: null,
        onInvalid: null,
        tags: [],
    },
};

jest.mock('loglevel', () => ({
    warn: jest.fn(),
    debug: jest.fn(),
}));

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'block':
                return require('../../../containers/FieldBlocks').default;
            case 'text_line':
                return require('../../../components/Input').default;
            case 'single_select':
                return require('../fields/SingleSelect').default;
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.data = resourceStore.data;
    this.locale = resourceStore.observableOptions.locale;
    this.loading = resourceStore.loading;
    this.validate = jest.fn().mockReturnValue(true);
    this.schema = {};
    this.set = jest.fn();
    this.change = jest.fn();
    this.finishField = jest.fn();
    this.isFieldModified = jest.fn();
    this.copyFromLocale = jest.fn();
    this.getValueByPath = jest.fn();
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({types: {default: {form: {}}}});
    this.types = {};
    this.changeType = jest.fn();
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.loading = false;
}));

jest.mock('../stores/metadataStore', () => ({
    getSchema: jest.fn(),
    getJsonSchema: jest.fn(),
}));

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    const form = render(<Form onSubmit={submitSpy} store={store} />);
    expect(form).toMatchSnapshot();
});

test('Render permission hint if permissions are missing', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.forbidden = true;

    const form = render(<Form onSubmit={submitSpy} store={store} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onError={errorSpy} onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(errorSpy).not.toBeCalled();
    expect(submitSpy).toBeCalled();
});

test.each([
    ['draft'],
    ['publish'],
])('Call saveHandlers with the action "%s" as argument when form is submitted', (action) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const submitPromise = Promise.resolve();
    const submitSpy = jest.fn().mockReturnValue(submitPromise);

    const resourceStore = new ResourceStore('snippet', '1');
    resourceStore.data = {
        block: [
            {
                text: 'Test',
                type: 'default',
            },
        ],
    };

    const store = new ResourceFormStore(resourceStore, 'snippet');

    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.instance().formInspector.addSaveHandler(handler1);
    form.instance().formInspector.addSaveHandler(handler2);

    form.instance().submit(action);

    return submitPromise.then(() => {
        expect(handler1).toBeCalledWith(action);
        expect(handler2).toBeCalledWith(action);
        expect(log.warn).toBeCalled();
    });
});

test.each([
    [undefined],
    [{action: 'draft'}],
    [{inherit: true}],
])('Call saveHandlers with the action "%s" as argument when form is submitted', (action) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const submitPromise = Promise.resolve();
    const submitSpy = jest.fn().mockReturnValue(submitPromise);

    const resourceStore = new ResourceStore('snippet', '1');
    resourceStore.data = {
        block: [
            {
                text: 'Test',
                type: 'default',
            },
        ],
    };

    const store = new ResourceFormStore(resourceStore, 'snippet');

    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.instance().formInspector.addSaveHandler(handler1);
    form.instance().formInspector.addSaveHandler(handler2);

    form.instance().submit(action);

    return submitPromise.then(() => {
        expect(handler1).toBeCalledWith(action);
        expect(handler2).toBeCalledWith(action);
        expect(log.warn).not.toBeCalled();
    });
});

test('Should call onError callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onError={errorSpy} onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(errorSpy).toBeCalledWith(store.errors);
    expect(submitSpy).not.toBeCalled();
});

test('Should work when errors occurs but no onError callback is given', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(submitSpy).not.toBeCalled();
});

test('Should validate form when a field has finished being edited', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    form.find('Renderer').prop('onFieldFinish')();

    expect(store.validate).toBeCalledWith();
});

test('Should validate form before calling finish handlers when a field has finished being edited', () => {
    const handler1 = jest.fn(() => {
        expect(validateCalled).toEqual(true);
    });
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);

    let validateCalled = false;
    store.validate.mockImplementation(() => validateCalled = true);
    form.find('Renderer').prop('onFieldFinish')();
});

test('Call finish handlers with dataPath and schemaPath when a section field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                },
            },
            type: 'section',
        },
    };
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);

    form.find('Field[name="title"] Input').prop('onFinish')();
    expect(handler1).toHaveBeenLastCalledWith('/title', '/highlight/items/title');
    expect(handler2).toHaveBeenLastCalledWith('/title', '/highlight/items/title');
});

test('Call finish handlers with dataPath and schemaPath when a field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.schema = {
        article: {
            type: 'text_line',
        },
    };
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);

    form.find('Field[name="article"] Input').prop('onFinish')();
    expect(handler1).toHaveBeenLastCalledWith('/article', '/article');
    expect(handler2).toHaveBeenLastCalledWith('/article', '/article');
});

test('Call finish handlers with dataPath and schemaPath when a block field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    resourceStore.data = {
        block: [
            {
                text: 'Test',
                type: 'default',
            },
        ],
    };

    const store = new ResourceFormStore(resourceStore, 'snippet');
    // $FlowFixMe
    store.schema = {
        block: {
            defaultType: 'default',
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);
    form.find('SortableBlockList').prop('onExpand')(0);
    form.update();
    form.find('SortableBlock Field').at(0).instance().handleFinish();
    expect(handler1).toHaveBeenCalledWith('/block/0/text', '/block/types/default/form/text');
    expect(handler1).toHaveBeenCalledWith('/block', '/block');
    expect(handler2).toHaveBeenCalledWith('/block/0/text', '/block/types/default/form/text');
    expect(handler2).toHaveBeenCalledWith('/block', '/block');
});

test('Should pass data, onSuccess, router and schema to Renderer', () => {
    const router = new Router();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const successSpy = jest.fn();

    // $FlowFixMe
    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';
    const form = shallow(<Form onSubmit={jest.fn()} onSuccess={successSpy} router={router} store={store} />);

    expect(form.find('Renderer').props()).toEqual(expect.objectContaining({
        data: store.data,
        onSuccess: successSpy,
        router,
        schema: store.schema,
        value: store.data,
    }));

    const formInspector = form.find('Renderer').prop('formInspector');
    expect(formInspector.resourceKey).toEqual('snippet');
    expect(formInspector.id).toEqual('1');
});

test('Should pass showAllErrors flag to Renderer when form has been submitted', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    expect(form.find('Renderer').prop('showAllErrors')).toEqual(false);
    form.find(Form).instance().submit();
    form.update();
    expect(form.find('Renderer').prop('showAllErrors')).toEqual(true);
});

test('Should change data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.find('Renderer').props().onChange('field', 'value', {isDefaultValue: true});
    expect(store.change).toBeCalledWith('field', 'value', {isDefaultValue: true});
});

test('Should change data on store without sections', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const form = mount(<Form onSubmit={submitSpy} store={store} />);
    form.find('Input').at(0).props().onChange('value!');

    expect(store.change).toBeCalledWith('item11', 'value!', undefined);
});

test('Should show a GhostDialog if the current locale is not translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(true);
});

test('Should not show a GhostDialog if the current locale is translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(false);
});

test('Should show a GhostDialog after the locale has been switched to a non-translated one', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(false);

    const {locale} = resourceStore.observableOptions;
    if (!locale) {
        throw new Error('The "locale" must be set!');
    }
    locale.set('de');

    form.update();
    expect(form.find('GhostDialog').prop('open')).toEqual(true);
});

test('Should not show a GhostDialog if the entity does not exist yet', () => {
    const resourceStore = new ResourceStore('snippet', undefined, {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog')).toHaveLength(0);
});

test('Should not show a GhostDialog if the entity is not translatable', () => {
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog')).toHaveLength(0);
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked', (resolve) => {
    metadataStore.getSchema.mockReturnValue(Promise.resolve(FORM));
    metadataStore.getJsonSchema.mockReturnValue(Promise.resolve({}));

    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    // $FlowFixMe
    metadataStore.getSchema().then(() => {
        setTimeout(() => {
            form.find('GhostDialog').update();

            form.find('GhostDialog SingleSelect').at(0).prop('onChange')('en');
            expect(form.find('GhostDialog').prop('open')).toEqual(true);
            form.find('GhostDialog Button[skin="primary"]').simulate('click');
            expect(form.find('GhostDialog').prop('open')).toEqual(false);

            expect(formStore.copyFromLocale).toBeCalledWith('en', {});

            resolve();
        }, 1);
    });
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked (with additional fields)', (resolve) => { // eslint-disable-line max-len
    const formMetadata = {
        ...FORM,
        title: {
            label: 'Test',
            disabledCondition: null,
            visibleCondition: null,
            description: '',
            type: 'text_line',
            colSpan: 6,
        },
    };
    metadataStore.getSchema.mockReturnValue(Promise.resolve(formMetadata));
    metadataStore.getJsonSchema.mockReturnValue(Promise.resolve({}));

    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    // $FlowFixMe
    metadataStore.getSchema().then(() => {
        setTimeout(() => {
            form.find('GhostDialog').update();

            form.find('GhostDialog Input').at(0).prop('onChange')('Test 123');
            form.find('GhostDialog SingleSelect').at(0).prop('onChange')('en');
            expect(form.find('GhostDialog').prop('open')).toEqual(true);
            form.find('GhostDialog Button[skin="primary"]').simulate('click');
            expect(form.find('GhostDialog').prop('open')).toEqual(false);

            expect(formStore.copyFromLocale).toBeCalledWith('en', {
                title: 'Test 123',
            });

            resolve();
        }, 1);
    });
});

test('Should show a GhostDialog and do nothing if the cancel button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(true);
    form.find('GhostDialog Button[skin="secondary"]').simulate('click');
    expect(form.find('GhostDialog').prop('open')).toEqual(false);

    expect(formStore.copyFromLocale).not.toBeCalled();
});

test('Should not show a GhostDialog if the resourceStore is currently loading', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = true;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.instance().displayGhostDialog).toEqual(false);
});

test('Should set the type of the formStore to selected value in MissingTypeDialog', () => {
    const onMissingTypeCancelSpy = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    // $FlowFixMe
    formStore.hasInvalidType = true;
    formStore.types = {
        default: {key: 'default', title: 'Default'},
    };

    const form = mount(<Form onMissingTypeCancel={onMissingTypeCancelSpy} onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('MissingTypeDialog').prop('open')).toEqual(true);
    form.find('MissingTypeDialog SingleSelect DisplayValue').simulate('click');
    form.find('MissingTypeDialog SingleSelect Option button').simulate('click');
    form.find('MissingTypeDialog Button[skin="primary"]').simulate('click');

    expect(onMissingTypeCancelSpy).not.toBeCalledWith();
    expect(formStore.changeType).toBeCalledWith('default');
});

test('Should call the onMissingTypeCancel callback if MissingTypeDialog is cancelled', () => {
    const onMissingTypeCancelSpy = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    // $FlowFixMe
    formStore.hasInvalidType = true;
    const form = mount(<Form onMissingTypeCancel={onMissingTypeCancelSpy} onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('MissingTypeDialog').prop('open')).toEqual(true);
    form.find('MissingTypeDialog Button[skin="secondary"]').simulate('click');

    expect(onMissingTypeCancelSpy).toBeCalledWith();
});
