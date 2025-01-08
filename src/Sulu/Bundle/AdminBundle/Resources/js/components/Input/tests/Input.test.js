// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Input from '../Input';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Input should render', () => {
    const onChange = jest.fn();
    const {container} = render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with autoFocus', () => {
    const onChange = jest.fn();
    const {container} = render(<Input autoFocus={true} onBlur={jest.fn()} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with autocomplete off', () => {
    const onChange = jest.fn();
    const {container} = render(<Input autocomplete="off" disabled={true} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render as headline', () => {
    const {container} = render(<Input headline={true} onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with invalid value', () => {
    const onChange = jest.fn();
    const {container} = render(<Input onBlur={jest.fn()} onChange={onChange} valid={false} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render when disabled', () => {
    const onChange = jest.fn();
    const {container} = render(<Input disabled={true} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with icon', () => {
    const onChange = jest.fn();
    const {container} = render(<Input icon="su-pen" onBlur={jest.fn()} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with inputmode', () => {
    const onChange = jest.fn();
    const {container} = render(<Input inputMode="numeric" onBlur={jest.fn()} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with type', () => {
    const onChange = jest.fn();
    const {container} = render(<Input onBlur={jest.fn()} onChange={onChange} type="password" value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with placeholder', () => {
    const onChange = jest.fn();
    const {container} = render(<Input
        onBlur={jest.fn()}
        onChange={onChange}
        placeholder="My placeholder"
        value="My value"
    />);
    expect(container).toMatchSnapshot();
});

test('Input should render with value', () => {
    const onChange = jest.fn();
    const {container} = render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('Input should render undefined value as empty string', () => {
    const onChange = jest.fn();
    const {container} = render(<Input onBlur={jest.fn()} onChange={onChange} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Input should render with a character counter', () => {
    const {container} = render(<Input maxCharacters={2} onBlur={jest.fn()} onChange={jest.fn()} value="asdf" />);
    expect(container).toMatchSnapshot();
});

test('Input should render with a segment counter', () => {
    const {container} = render(
        <Input
            maxSegments={3}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            segmentDelimiter=","
            value="keyword1, keyword2"
        />
    );
    expect(container).toMatchSnapshot();
});

test('Input should call the callback when the input changes', async() => {
    const onChange = jest.fn();
    render(bindValueToOnChange(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />));

    const input = screen.queryByDisplayValue('My value');
    await userEvent.type(input, ' - changed');

    expect(onChange).toHaveBeenLastCalledWith('My value - changed', expect.anything());
});

test('Input should call the callback with undefined if the input value is removed', async() => {
    const onChange = jest.fn();
    render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);

    const input = screen.queryByDisplayValue('My value');
    await userEvent.clear(input);

    expect(onChange).toHaveBeenCalledWith(undefined, expect.anything());
});

test('Input should call the callback when icon was clicked', async() => {
    const onChange = jest.fn();
    const handleIconClick = jest.fn();
    render(<Input icon="su-pen" onChange={onChange} onIconClick={handleIconClick} value="My value" />);

    const icon = screen.queryByLabelText('su-pen');
    await userEvent.click(icon);

    expect(handleIconClick).toHaveBeenCalled();
});

test('Input should call the given focus callback', async() => {
    const onFocusSpy = jest.fn();

    render(<Input icon="su-pen" onChange={jest.fn()} onFocus={onFocusSpy} value="My value" />);

    const input = screen.queryByDisplayValue('My value');

    expect(onFocusSpy).not.toHaveBeenCalled();
    await userEvent.click(input);
    expect(onFocusSpy).toHaveBeenCalled();
});

test('Input should render with a loader', () => {
    const onChange = jest.fn();
    const {container} = render(<Input loading={true} onBlur={jest.fn()} onChange={onChange} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Input should render collapsed', () => {
    const {container} = render(<Input collapsed={true} onChange={jest.fn()} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Input should render append container when onClearClick callback is provided', () => {
    const {container} = render(<Input onChange={jest.fn()} onClearClick={jest.fn()} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Input should render append container with icon when onClearClick callback is provided and value is set', () => {
    const {container} = render(<Input onChange={jest.fn()} onClearClick={jest.fn()} value="test" />);
    expect(container).toMatchSnapshot();
});

test('Input should should call the callback when clear icon was clicked', async() => {
    const onClearClick = jest.fn();
    render(<Input onChange={jest.fn()} onClearClick={onClearClick} value="My value" />);

    const icon = screen.queryByLabelText('su-times');
    await userEvent.click(icon);
    expect(onClearClick).toHaveBeenCalled();
});

test('Input should render with dark skin', () => {
    const {container} =
        render(<Input icon="su-pen" onChange={jest.fn()} onClearClick={jest.fn()} skin="dark" value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Input should render with type number with attributes', () => {
    const {container} =
        render(<Input max={50} min={10} onBlur={jest.fn()} onChange={jest.fn()} step={5} type="number" value={25} />);
    expect(container).toMatchSnapshot();
});

test('Input should call onFocus when the Input gets focus', async() => {
    const focusSpy = jest.fn();
    render(bindValueToOnChange(<Input onChange={jest.fn()} onFocus={focusSpy} value="My value" />));

    const input = screen.queryByDisplayValue('My value');
    input.focus();

    expect(focusSpy).toHaveBeenCalled();
});
