// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import PromptInput from '../PromptInput';

jest.mock('../../../containers', () => ({
    TextEditor: jest.fn(({value}) => <div data-testid="text-editor">{value}</div>),
}));

jest.mock('../../../utils', () => ({
    translate: (key) => key,
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

describe('PromptInput Component', () => {
    const defaultProps = {
        experts: {
            name: 'Expert Name',
            text: 'Expert Text',
            type: 'text',
        },
        isLoading: false,
        onAddMessage: jest.fn().mockResolvedValue(undefined),
        predefinedPrompts: null,
        messages: {
            send: 'Send',
            addMessage: 'Add Message',
        },
    };

    test('renders the expert text when type is text', () => {
        render(<PromptInput {...defaultProps} />);

        expect(screen.getAllByText(defaultProps.experts.text)[0]).toBeInTheDocument();
    });

    test('renders the SingleSelect when type is select', async() => {
        const selectExperts = {
            name: 'Expert Name',
            options: [
                {id: '1', name: 'Option 1'},
                {id: '2', name: 'Option 2'},
            ],
            selected: '1',
            type: 'select',
            handleClick: jest.fn(),
        };

        render(<PromptInput {...defaultProps} experts={selectExperts} />);

        await userEvent.click(screen.getAllByText('Option 1')[0]);

        expect(screen.getAllByText('Option 1')[0]).toBeInTheDocument();
        expect(screen.getAllByText('Option 2')[0]).toBeInTheDocument();
    });

    test('renders the predefined prompts dropdown when predefinedPrompts is provided', async() => {
        const predefinedPrompts = {
            handleClick: jest.fn(),
            label: 'Predefined Prompts',
            options: [
                {id: 1, name: 'Prompt 1'},
                {id: 2, name: 'Prompt 2'},
            ],
        };

        render(<PromptInput {...defaultProps} predefinedPrompts={predefinedPrompts} />);

        expect(screen.getByText(predefinedPrompts.label)).toBeInTheDocument();
        await userEvent.click(screen.getByText(predefinedPrompts.label));
        expect(screen.getByText('Prompt 1')).toBeInTheDocument();
        expect(screen.getByText('Prompt 2')).toBeInTheDocument();
    });

    test('calls onAddMessage when the send button is clicked', async() => {
        render(<PromptInput {...defaultProps} />);

        const input = screen.getByPlaceholderText('Add Message');
        await userEvent.type(input, 'Test message');
        await userEvent.click(screen.getByText('Send'));

        expect(defaultProps.onAddMessage).toHaveBeenCalledWith('Test message');
    });

    test('calls onAddMessage when Enter key is pressed', async() => {
        render(<PromptInput {...defaultProps} />);

        const input = screen.getByPlaceholderText('Add Message');
        await userEvent.type(input, 'Test message{enter}');

        expect(defaultProps.onAddMessage).toHaveBeenCalledWith('Test message');
    });

    test('does not call onAddMessage when input is empty', async() => {
        render(<PromptInput {...defaultProps} />);

        await userEvent.click(screen.getByRole('button', 'Send'));

        expect(defaultProps.onAddMessage).not.toHaveBeenCalled();
    });

    test('disables the send button when input is empty', () => {
        render(<PromptInput {...defaultProps} />);

        const button = screen.getByRole('button', 'Send');
        expect(button).toBeDisabled();
    });

    test('enables the send button when input is not empty', async() => {
        render(<PromptInput {...defaultProps} />);

        const input = screen.getByPlaceholderText('Add Message');
        await userEvent.type(input, 'Test message');

        const button = screen.getByText('Send');
        expect(button).toBeEnabled();
    });
});
