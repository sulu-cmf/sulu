// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import Input from '../Input';
import {TextEditor} from '../../../containers';

jest.mock('../../../containers', () => ({
    TextEditor: jest.fn(() => <div data-testid="text-editor" />),
}));

jest.mock('../translator.scss', () => ({
    input: 'input-class',
    textarea: 'textarea-class',
    texteditor: 'texteditor-class',
}));

describe('Input', () => {
    test('renders textarea for text_line and text_area types', () => {
        render(<Input text="Test" type="text_line" />);
        expect(screen.getByRole('textbox')).toBeInTheDocument();
    });

    test('renders TextEditor for text_editor type', () => {
        render(<Input text="Test" type="text_editor" />);
        expect(screen.getByTestId('text-editor')).toBeInTheDocument();
    });

    test('calls onChange when textarea value changes', async() => {
        const text = observable.box('Initial');
        const onChange = (value) => text.set(value);

        // eslint-disable-next-line react/jsx-no-bind
        render(<Input onChange={onChange} text={text} type="text_area" />);

        const textarea = screen.getByRole('textbox');
        await userEvent.type(textarea, ' Text');

        expect(text.get()).toBe('Initial Text');
    });

    test('calls onChange when TextEditor value changes', () => {
        const onChange = jest.fn();
        render(<Input onChange={onChange} text="Initial" type="text_editor" />);

        // $FlowFixMe
        const textEditor = TextEditor.mock.calls[0][0];
        textEditor.onChange('New Text');

        expect(onChange).toHaveBeenCalledWith('New Text');
    });

    test('applies correct CSS classes to textarea', () => {
        render(<Input text="Test" type="text_line" />);
        const textarea = screen.getByRole('textbox');
        expect(textarea).toHaveClass('input-class', 'textarea-class');
    });

    test('applies correct CSS classes to TextEditor wrapper', () => {
        render(<Input text="Test" type="text_editor" />);
        const wrapper = screen.getByTestId('text-editor').parentElement;
        expect(wrapper).toHaveClass('input-class', 'texteditor-class');
    });

    test('disables TextEditor when onChange is not provided', () => {
        render(<Input text="Test" type="text_editor" />);

        // $FlowFixMe
        const textEditor = TextEditor.mock.calls[0][0];
        expect(textEditor.disabled).toBe(true);
    });
});
