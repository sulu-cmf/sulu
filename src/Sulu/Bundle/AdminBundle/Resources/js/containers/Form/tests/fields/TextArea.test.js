// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import TextArea from '../../fields/TextArea';
import TextAreaComponent from '../../../../components/TextArea';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to Input component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <TextArea
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={'xyz'}
        />
    );

    expect(inputInvalid.find(TextAreaComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to Input component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const inputValid = shallow(
        <TextArea
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={'xyz'}
        />
    );

    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
});
