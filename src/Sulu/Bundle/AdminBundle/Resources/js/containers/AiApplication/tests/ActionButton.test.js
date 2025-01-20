// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import ActionButton from '../ActionButton';

describe('ActionButton', () => {
    const defaultProps = {
        messages: {
            title: 'Action',
        },
        onClick: jest.fn(),
    };

    it('renders without crashing', () => {
        render(<ActionButton {...defaultProps} />);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('renders the title message', () => {
        render(<ActionButton {...defaultProps} />);
        expect(screen.getByText('Action')).toBeInTheDocument();
    });

    it('calls onClick when the button is clicked', async() => {
        render(<ActionButton {...defaultProps} />);
        await userEvent.click(screen.getByRole('button'));
        expect(defaultProps.onClick).toHaveBeenCalled();
    });

    it('applies the correct class names', () => {
        const {container} = render(<ActionButton {...defaultProps} />);
        expect(container.firstChild).toHaveClass('feedbackContainer');
        expect(screen.getByRole('button')).toHaveClass('feedbackTab');
        expect(screen.getByRole('button').firstChild).toHaveClass('feedbackIcon');
    });
});
