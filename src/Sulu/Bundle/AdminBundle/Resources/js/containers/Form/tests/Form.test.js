// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';
import ResourceStore from '../../../stores/ResourceStore';
import FormStore from '../stores/FormStore';
import metadataStore from '../stores/MetadataStore';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'block':
                return require('../../../containers/FieldBlocks').default;
            case 'text_line':
                return require('../../../components/Input').default;
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../stores/FormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.data = resourceStore.data;
    this.validate = jest.fn();
    this.schema = {};
    this.set = jest.fn();
    this.change = jest.fn();
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(function (resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.data = {};
}));

jest.mock('../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
}));

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));

    const form = render(<Form store={store} onSubmit={submitSpy} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(submitSpy).toBeCalled();
});

test('Should validate form when a field has finished being edited', () => {
    const store = new FormStore(new ResourceStore('snippet', '1'));
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    form.find('Renderer').prop('onFieldFinish')();

    expect(store.validate).toBeCalledWith();
});

test('Call finish handlers on formInspector when a section field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new FormStore(new ResourceStore('snippet', '1'));
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
    expect(handler1).toHaveBeenLastCalledWith('/highlight/items/title');
    expect(handler2).toHaveBeenLastCalledWith('/highlight/items/title');
});

test('Call finish handlers on formInspector when a field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new FormStore(new ResourceStore('snippet', '1'));
    store.schema = {
        article: {
            type: 'text_line',
        },
    };
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);

    form.find('Field[name="article"] Input').prop('onFinish')();
    expect(handler1).toHaveBeenLastCalledWith('/article');
    expect(handler2).toHaveBeenLastCalledWith('/article');
});

test('Call finish handlers with the formStore and schemaPath when a block field has finished being edited', () => {
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

    const store = new FormStore(resourceStore);
    store.schema = {
        block: {
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
    form.find('SortableBlocks').prop('onExpand')(0);
    form.update();
    form.find('SortableBlock Field').instance().handleFinish();
    expect(handler1).toHaveBeenLastCalledWith('/block/types/default/form/text');
    expect(handler2).toHaveBeenLastCalledWith('/block/types/default/form/text');
});

test('Should pass formInspector, schema, data and showAllErrors flag to Renderer', () => {
    const store = new FormStore(new ResourceStore('snippet', '1'));
    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';
    const form = shallow(<Form onSubmit={jest.fn()} store={store} />);

    expect(form.find('Renderer').props()).toEqual(expect.objectContaining({
        data: store.data,
        schema: store.schema,
    }));

    const formInspector = form.find('Renderer').prop('formInspector');
    expect(formInspector.resourceKey).toEqual('snippet');
    expect(formInspector.id).toEqual('1');
});

test('Should pass showAllErrors flag to Renderer when form has been submitted', () => {
    const store = new FormStore(new ResourceStore('snippet', '1'));
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    expect(form.find('Renderer').prop('showAllErrors')).toEqual(false);
    form.find('Form').instance().submit();
    form.update();
    expect(form.find('Renderer').prop('showAllErrors')).toEqual(true);
});

test('Should change data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.find('Renderer').simulate('change', 'field', 'value');
    expect(store.change).toBeCalledWith('field', 'value');
});

test('Should change data on store without sections', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
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

    const form = mount(<Form store={store} onSubmit={submitSpy} />);
    form.find('Input').at(0).instance().handleChange({currentTarget: {value: 'value!'}});

    expect(store.change).toBeCalledWith('item11', 'value!');
});
