/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import GenericSelect from '../GenericSelect';
import Divider from '../Divider';
import Option from '../Option';

afterEach(() => document.body.innerHTML = '');

test('The component should render with the popover closed', () => {
    const body = document.body;
    const isOptionSelected = () => false;
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            onSelect={onSelect}
            isOptionSelected={isOptionSelected}
            displayValue="My text"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should render with an icon', () => {
    const body = document.body;
    const isOptionSelected = () => false;
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            icon="plus"
            onSelect={onSelect}
            isOptionSelected={isOptionSelected}
            displayValue="My text"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should open the popover when the display value is clicked', () => {
    const body = document.body;
    const isOptionSelected = () => false;
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            onSelect={onSelect}
            isOptionSelected={isOptionSelected}
            displayValue="My text"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    select.instance().handleDisplayValueClick();
    expect(select.render()).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should trigger the select callback and close the popover when an option is clicked', () => {
    const body = document.body;
    const onSelectSpy = jest.fn();
    const isOptionSelected = () => false;
    const select = mount(
        <GenericSelect
            onSelect={onSelectSpy}
            isOptionSelected={isOptionSelected}
            displayValue="My text"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    select.instance().handleDisplayValueClick();
    body.getElementsByTagName('button')[2].click();
    expect(onSelectSpy).toHaveBeenCalledWith('option-3');
    expect(body.innerHTML).toBe('');
});

test('The component should pass the centered child node to the popover', () => {
    const onSelect = () => {};
    const isOptionSelected = (child) => child.props.value === 'option-3';
    const selectedOption = (<Option value="option-3">Option 3</Option>);
    const select = mount(
        <GenericSelect
            onSelect={onSelect}
            isOptionSelected={isOptionSelected}
            displayValue="My text"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            {selectedOption}
        </GenericSelect>
    );

    const popover = select.find('Popover');
    expect(popover.props().centerChildNode).toBe(mount(selectedOption).get(0).innerHTML);
});

test('The component should pass the selected property to the options', () => {
    const isOptionSelected = () => true;
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            onSelect={onSelect}
            isOptionSelected={isOptionSelected}
            displayValue="My text"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    select.instance().handleDisplayValueClick();
    expect(document.body.querySelectorAll('.selected').length).toBe(3);
});
