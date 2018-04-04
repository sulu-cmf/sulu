// @flow
import React from 'react';
import {shallow} from 'enzyme';
import TextArea from '../../fields/TextArea';
import TextAreaComponent from '../../../../components/TextArea';

test('Pass error correctly to Input component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <TextArea
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
            error={error}
        />
    );

    expect(inputInvalid.find(TextAreaComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to Input component', () => {
    const inputValid = shallow(
        <TextArea
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
        />
    );

    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
});
