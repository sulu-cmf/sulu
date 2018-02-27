// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Input from '../Input';

test('Input should render', () => {
    const onChange = jest.fn();
    expect(render(<Input value="My value" onChange={onChange} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with invalid value', () => {
    const onChange = jest.fn();
    const error = {keyword: 'minLength', parameters: {}};
    expect(render(<Input error={error} value="My value" onChange={onChange} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with icon', () => {
    const onChange = jest.fn();
    expect(render(<Input icon="su-pen" value="My value" onChange={onChange} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with type', () => {
    const onChange = jest.fn();
    expect(render(
        <Input type="password" value="My value" onChange={onChange} onFinish={jest.fn()} />
    )).toMatchSnapshot();
});

test('Input should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(
        <Input placeholder="My placeholder" value="My value" onChange={onChange} onFinish={jest.fn()} />
    )).toMatchSnapshot();
});

test('Input should render with value', () => {
    const onChange = jest.fn();
    expect(render(<Input value="My value" onChange={onChange} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render null value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Input value={null} onChange={onChange} onFinish={jest.fn()} />)).toMatchSnapshot();
});

test('Input should call the callback when the input changes', () => {
    const onChange = jest.fn();
    const input = shallow(<Input value="My value" onChange={onChange} onFinish={jest.fn()} />);
    input.find('input').simulate('change', {currentTarget: {value: 'my-value'}});
    expect(onChange).toHaveBeenCalledWith('my-value');
});

test('Input should call the callback with undefined if the input is removed', () => {
    const onChange = jest.fn();
    const input = shallow(<Input value="My value" onChange={onChange} onFinish={jest.fn()} />);
    input.find('input').simulate('change', {currentTarget: {value: ''}});
    expect(onChange).toHaveBeenCalledWith(undefined);
});

test('Input should render with a loader', () => {
    const onChange = jest.fn();
    expect(render(<Input value={null} loader={true} onChange={onChange} onFinish={jest.fn()} />)).toMatchSnapshot();
});
