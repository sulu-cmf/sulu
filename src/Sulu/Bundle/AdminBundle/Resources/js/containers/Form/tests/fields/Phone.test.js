// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Phone from '../../fields/Phone';
import PhoneComponent from '../../../../components/Phone';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <Phone
            dataPath=""
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'xyz'}
        />
    );

    expect(field.find(PhoneComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const field = shallow(
        <Phone
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'xyz'}
        />
    );

    expect(field.find(PhoneComponent).prop('valid')).toBe(true);
});
