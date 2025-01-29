// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import Message from '../Message';

jest.mock('../../../containers', () => ({
    TextEditor: jest.fn(({value}) => <div data-testid="text-editor">{value}</div>),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

describe('Message Component', () => {
    const defaultProps = {
        expert: 'Test Expert',
        collapsed: false,
        command: 'Test Command',
        displayActions: true,
        index: 0,
        isLoading: false,
        locale: 'en',
        onClick: jest.fn(),
        onCopy: jest.fn(),
        onInsert: jest.fn(),
        onRetry: jest.fn(),
        text: 'This is a test message.',
        type: 'text_line',
    };

    test('renders the commandTitle and text', () => {
        render(<Message {...defaultProps} />);

        expect(screen.getByText(defaultProps.command)).toBeInTheDocument();
        expect(screen.getByText(defaultProps.text)).toBeInTheDocument();
    });

    test('renders the expert if provided', () => {
        render(<Message {...defaultProps} expert="Test Expert" />);

        expect(screen.getByText('Test Expert')).toBeInTheDocument();
    });

    test('renders the actions buttons if displayActions is true', () => {
        render(<Message {...defaultProps} />);

        expect(screen.getByText('sulu_admin.insert')).toBeInTheDocument();
        expect(screen.getAllByRole('button', {name: /su-sync/i})[0]).toBeInTheDocument();
        expect(screen.getAllByRole('button', {name: /su-copy/i})[0]).toBeInTheDocument();
    });

    test('does not render actions buttons if displayActions is false', () => {
        render(<Message {...defaultProps} displayActions={false} />);

        expect(screen.queryByText('sulu_admin.insert')).not.toBeInTheDocument();
        expect(screen.queryAllByRole('button', {name: /su-sync/i})[0]).toBeUndefined();
        expect(screen.queryAllByRole('button', {name: /su-copy/i})[0]).toBeUndefined();
    });

    test('calls onClick when the message is clicked', async() => {
        render(<Message {...defaultProps} />);

        await userEvent.click(screen.getByText(defaultProps.text));

        expect(defaultProps.onClick).toHaveBeenCalledWith(defaultProps.index);
    });

    test('calls onRetry when the retry button is clicked', async() => {
        render(<Message {...defaultProps} />);

        await userEvent.click(screen.getAllByRole('button', {name: /su-sync/i})[1]);

        expect(defaultProps.onRetry).toHaveBeenCalledWith(defaultProps.index);
    });

    test('calls onCopy when the copy button is clicked', async() => {
        render(<Message {...defaultProps} />);

        await userEvent.click(screen.getAllByRole('button', {name: /su-copy/i})[1]);

        expect(defaultProps.onCopy).toHaveBeenCalledWith(defaultProps.text);
    });

    test('calls onInsert when the insert button is clicked', async() => {
        render(<Message {...defaultProps} />);

        await userEvent.click(screen.getByText('sulu_admin.insert'));

        expect(defaultProps.onInsert).toHaveBeenCalledWith(defaultProps.text);
    });

    test('renders trimmed text when collapsed is true', () => {
        const longText = 'This is a very long text that should be trimmed when collapsed '
            + 'and only 70 characters should be shown followed by "...".';
        render(<Message {...defaultProps} collapsed={true} text={longText} />);

        expect(screen.getByText(
            /This is a very long text that should be trimmed when collapsed and ... followed by "..."/
        )).toBeInTheDocument();
    });

    test('renders TextEditor when type is text_editor and not collapsed', () => {
        render(<Message {...defaultProps} type="text_editor" />);

        expect(screen.getByText(defaultProps.text)).toBeInTheDocument();
    });
});
