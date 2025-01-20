// @flow

import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import MemoryFormStoreFactory from '../../Form/stores/memoryFormStoreFactory';
import ActionOverlay from '../ActionOverlay';

// Mock the Requester and MemoryFormStoreFactory
jest.mock('../../../services', () => ({
    Requester: {
        post: jest.fn(() => Promise.resolve()),
    },
}));

jest.mock('../../Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        data: {},
        dirty: true,
        validate: jest.fn(() => true),
        hasInvalidType: false,
        types: {},
        isFieldModified: jest.fn(() => false),
        change: jest.fn(),
        schema: {
            subject: {
                label: 'Betreff',
                disabledCondition: null,
                visibleCondition: null,
                description: '',
                type: 'text_line',
                colSpan: 12,
                options: [],
                types: [],
                defaultType: null,
                required: true,
                spaceAfter: null,
                minOccurs: null,
                maxOccurs: null,
                onInvalid: null,
                tags: [],
            },
        },
    })),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../Form/registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return require('../../../components/Input').default;
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

describe('ActionOverlay', () => {
    const defaultProps = {
        formKey: 'testFormKey',
        source: 'testSource',
        url: 'testUrl',
    };

    it('renders without crashing', () => {
        render(<ActionOverlay {...defaultProps} />);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('renders the button with the correct title', () => {
        render(<ActionOverlay {...defaultProps} />);
        expect(screen.getByText('sulu_admin.ai_action_button')).toBeInTheDocument();
    });

    it('opens the form overlay when the button is clicked', async() => {
        render(<ActionOverlay {...defaultProps} />);
        await userEvent.click(screen.getByRole('button'));
        expect(MemoryFormStoreFactory.createFromFormKey).toHaveBeenCalledWith('testFormKey');
        expect(screen.getByText('sulu_admin.ai_action_title')).toBeInTheDocument();
    });

    it('handles form close', async() => {
        render(<ActionOverlay {...defaultProps} />);
        await userEvent.click(screen.getByRole('button'));
        await userEvent.click(screen.getAllByRole('button', {name: /su-times/i})[0]);

        await waitFor(() => {
            expect(screen.queryByText('Form Title')).not.toBeInTheDocument();
        });
    });
});
