// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TextArea from '../TextArea';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('TextArea should render', () => {
    const {container} = render(<TextArea onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with error', () => {
    const {container} = render(<TextArea onChange={jest.fn()} valid={false} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with placeholder', () => {
    const {container} = render(<TextArea onChange={jest.fn()} placeholder="My placeholder" value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with value', () => {
    const {container} = render(<TextArea onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render when disabled', () => {
    const {container} = render(<TextArea disabled={true} onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render null value as empty string', () => {
    const {container} = render(<TextArea onChange={jest.fn()} value={null} />);
    expect(container).toMatchSnapshot();
});

test('TextArea should render with value and character counter', () => {
    const {container} = render(<TextArea maxCharacters={10} onChange={jest.fn()} value="My value" />);
    expect(container).toMatchSnapshot();
});

test('TextArea should call onBlur when it loses focus', async() => {
    const blurSpy = jest.fn();
    render(<TextArea onBlur={blurSpy} onChange={jest.fn()} value="" />);

    const textarea = screen.queryByRole('textbox');

    await userEvent.click(textarea);
    expect(blurSpy).not.toBeCalledWith();

    await userEvent.tab();
    expect(blurSpy).toBeCalledWith();
});

test('TextArea should call onChange when the TextArea changes', async() => {
    const changeSpy = jest.fn();
    render(bindValueToOnChange(<TextArea onChange={changeSpy} value="My value" />));

    const textarea = screen.queryByDisplayValue('My value');
    await userEvent.type(textarea, ' - changed');

    expect(changeSpy).toHaveBeenLastCalledWith('My value - changed');
});

test('TextArea should call onChange with undefined when the TextArea changes to empty', async() => {
    const changeSpy = jest.fn();
    render(<TextArea onChange={changeSpy} value="My value" />);

    const textarea = screen.queryByDisplayValue('My value');
    await userEvent.clear(textarea);

    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('TextArea should call onFocus when the TextArea gets focus', async() => {
    const focusSpy = jest.fn();
    render(bindValueToOnChange(<TextArea onChange={jest.fn()} onFocus={focusSpy} value="My value" />));

    const textarea = screen.queryByDisplayValue('My value');
    textarea.focus();

    expect(focusSpy).toHaveBeenCalled();
});
