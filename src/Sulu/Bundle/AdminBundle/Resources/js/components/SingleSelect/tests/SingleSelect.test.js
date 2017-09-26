/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import Select from '../../Select';
import SingleSelect from '../../SingleSelect';

const Option = SingleSelect.Option;
const Divider = SingleSelect.Option;

jest.mock('../../Select');

test('The component should render a generic select', () => {
    const select = shallow(
        <SingleSelect>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    expect(select.node.type).toBe(Select);
});

test('The component should return the first option as default display value', () => {
    const select = shallow(
        <SingleSelect>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('Option 1');
});

test('The component should return the correct displayValue', () => {
    const select = shallow(
        <SingleSelect value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('Option 2');
});

test('The component should select the correct option', () => {
    const select = shallow(
        <SingleSelect value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const isOptionSelected = select.find(Select).props().isOptionSelected;
    expect(isOptionSelected({props: {value: 'option-1', disabled: false}})).toBe(false);
    expect(isOptionSelected({props: {value: 'option-2', disabled: false}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-3', disabled: false}})).toBe(false);
});

test('The component should trigger the change callback on select', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <SingleSelect value="option-2" onChange={onChangeSpy}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    select.find(Select).props().onSelect('option-3');
    expect(onChangeSpy).toHaveBeenCalledWith('option-3');
});
