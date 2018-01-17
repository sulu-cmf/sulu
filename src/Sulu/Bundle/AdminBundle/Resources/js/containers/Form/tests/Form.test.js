// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';
import ResourceStore from '../../../stores/ResourceStore';
import FormStore from '../stores/FormStore';
import metadataStore from '../stores/MetadataStore';

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return require('../../../components/Input').default;
        }
    }),
}));

jest.mock('../stores/FormStore', () => jest.fn(function() {
    this.schema = {};
    this.data = {};
    this.set = jest.fn();
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
}));

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));

    const form = render(<Form store={store} onSubmit={submitSpy} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback on submit', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={submitSpy} store={store} />);
    form.instance().submit();

    expect(submitSpy).toBeCalled();
});

test('Should pass schema and data to renderer', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    expect(form.find('Renderer').props().schema).toBe(store.schema);
    expect(form.find('Renderer').props().data).toBe(store.data);
});

test('Should set data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new FormStore(new ResourceStore('snippet', '1'));
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.find('Renderer').simulate('change', 'field', 'value');
    expect(store.set).toBeCalledWith('field', 'value');
});

test('Should set data on store without sections', () => {
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
    form.find('Input').get(0).handleChange({currentTarget: {value: 'value!'}});

    expect(store.set).toBeCalledWith('item11', 'value!');
});
