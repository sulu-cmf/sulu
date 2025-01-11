// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Requester from '../../../services/Requester';
import Translator from '../Translator';

jest.mock('../../../services/Requester');
jest.mock('debounce', () => jest.fn((fn) => fn));
jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

const mockProps = {
    locale: 'en',
    value: 'Hallo',
    onConfirm: jest.fn(),
    onDialogClose: jest.fn(),
    type: 'text_line',
    url: '/api/translate',
    messages: {
        title: 'Translate',
        insert: 'Insert',
        detected: 'Detected',
        errorTranslatingText: 'Error translating text',
    },
    sourceLanguages: [
        {locale: 'en', label: 'English'},
        {locale: 'de', label: 'German'},
    ],
    targetLanguages: [
        {locale: 'fr', label: 'French'},
        {locale: 'es', label: 'Spanish'},
    ],
};

describe('Translator', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders correctly with initial props', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);

        expect(screen.getByText('Translate')).toBeInTheDocument();
        expect(screen.getByText('Insert')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Hallo')).toBeInTheDocument();

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: undefined,
                targetLanguage: 'en',
            });
        });

        expect(screen.getByDisplayValue('Hello')).toBeInTheDocument();
    });

    test('translates text when source text changes', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);

        Requester.post.mockResolvedValue({
            response: {text: 'Bye', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        const sourceInput = screen.getByDisplayValue('Hallo');
        await userEvent.clear(sourceInput);
        await userEvent.type(sourceInput, 'Auf wiedersehen');

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Auf wiedersehen',
                sourceLanguage: undefined,
                targetLanguage: 'en',
            });
        });

        expect(screen.getByDisplayValue('Bye')).toBeInTheDocument();
    });

    test('changes source language', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);
        await waitFor(() => {
            expect(screen.getByText('Hello')).toBeInTheDocument();
        });

        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: 'de', targetLanguage: 'en'},
        });

        await userEvent.click(screen.getAllByTitle('sulu_admin.please_choose')[0]);
        await waitFor(() => {
            expect(screen.getByText('German')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('German'));

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: 'de',
                targetLanguage: 'en',
            });
        });
    });

    test('changes target language', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);
        await waitFor(() => {
            expect(screen.getByText('Hello')).toBeInTheDocument();
        });

        Requester.post.mockResolvedValue({
            response: {text: 'Hola', sourceLanguage: undefined, targetLanguage: 'fr'},
        });

        await userEvent.click(screen.getAllByTitle('sulu_admin.please_choose')[1]);
        await waitFor(() => {
            expect(screen.getByText('French')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('French'));

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: undefined,
                targetLanguage: 'fr',
            });
        });

        expect(screen.getByDisplayValue('Hola')).toBeInTheDocument();
    });

    test('handles translation error', async() => {
        Requester.post.mockRejectedValue(new Error('Error translating text'));

        render(<Translator {...mockProps} />);

        await waitFor(() => {
            const errorElement = screen.getAllByText((content, element) => {
                return element.textContent.includes('Error translating text');
            })[0];

            expect(errorElement).toBeInTheDocument();
        });
    });

    test('calls onConfirm with translated text', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Bonjour', sourceLanguage: 'EN', targetLanguage: 'FR'},
        });

        render(<Translator {...mockProps} />);

        await waitFor(() => {
            expect(screen.getByDisplayValue('Bonjour')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('Insert'));

        expect(mockProps.onConfirm).toHaveBeenCalledWith('Bonjour');
    });

    test('calls onDialogClose when closing', async() => {
        render(<Translator {...mockProps} />);

        const closeButton = screen.getAllByRole('button', {name: /su-times/i})[0];
        await userEvent.click(closeButton);

        expect(mockProps.onDialogClose).toHaveBeenCalled();
    });

    test('calls action prop with correct parameters when translation occurs', async() => {
        const mockAction = jest.fn(() => <div>Test Action</div>);
        const props = {
            ...mockProps,
            // $FlowFixMe
            action: mockAction,
        };

        Requester.post.mockResolvedValue({
            response: {
                text: 'Bonjour',
                sourceLanguage: 'EN',
                targetLanguage: 'FR',
            },
        });

        render(<Translator {...props} />);

        await waitFor(() => {
            expect(screen.getByText('Test Action')).toBeInTheDocument();
        });

        expect(mockAction).toHaveBeenCalledTimes(3);
        expect(mockAction).toHaveBeenNthCalledWith(3, {
            source: 'translator',
            context: {
                response: {
                    text: 'Bonjour',
                    sourceLanguage: 'EN',
                    targetLanguage: 'FR',
                },
            },
        }, {});
    });
});
