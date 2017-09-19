/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import GenericSelect from '../../GenericSelect';
import Select from '../../Select';

const Option = Select.Option;
const Divider = Select.Option;

jest.mock('../../GenericSelect');

test('The component should render a generic select', () => {
    const select = shallow(
        <Select>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.node.type).toBe(GenericSelect);
});

test('The component should return the first option as default display value', () => {
    const select = shallow(
        <Select>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    const displayValue = select.find(GenericSelect).props().displayValue;
    expect(displayValue).toBe('Option 1');
});

test('The component should return the correct displayValue', () => {
    const select = shallow(
        <Select value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    const displayValue = select.find(GenericSelect).props().displayValue;
    expect(displayValue).toBe('Option 2');
});

test('The component should select the correct option', () => {
    const select = shallow(
        <Select value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    const isOptionSelected = select.find(GenericSelect).props().isOptionSelected;
    expect(isOptionSelected({props: {value: 'option-1', disabled: false}})).toBe(false);
    expect(isOptionSelected({props: {value: 'option-2', disabled: false}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-3', disabled: false}})).toBe(false);
});

test('The component should trigger the change callback on select', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <Select value="option-2" onChange={onChangeSpy}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.find(GenericSelect).props().onSelect('option-3');
    expect(onChangeSpy).toHaveBeenCalledWith('option-3');
});
