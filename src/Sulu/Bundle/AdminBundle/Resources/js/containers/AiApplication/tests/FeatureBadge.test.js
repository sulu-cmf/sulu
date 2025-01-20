// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import FeatureBadge from '../FeatureBadge';

describe('FeatureBadge', () => {
    const defaultProps = {
        messages: {
            translate: 'Translate',
            writingAssistant: 'Writing Assistant',
        },
        skin: 'white',
    };

    it('renders without crashing', () => {
        render(<FeatureBadge {...defaultProps} />);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('renders with white skin class', () => {
        const {container} = render(<FeatureBadge {...defaultProps} />);
        expect(container.firstChild.firstChild).toHaveClass('contentWhite');
    });

    it('renders with gray skin class', () => {
        const {container} = render(<FeatureBadge {...defaultProps} skin="gray" />);
        expect(container.firstChild.firstChild).toHaveClass('contentGray');
    });

    it('calls onWritingAssistantClick when WritingAssistantIcon is clicked', async() => {
        const handleWritingAssistantClick = jest.fn();
        render(
            <FeatureBadge {...defaultProps} onWritingAssistantClick={handleWritingAssistantClick} />
        );
        await userEvent.click(screen.getByTitle('Writing Assistant'));
        expect(handleWritingAssistantClick).toHaveBeenCalled();
    });

    it('calls onTranslateClick when TranslateIcon is clicked', async() => {
        const handleTranslateClick = jest.fn();
        render(
            <FeatureBadge {...defaultProps} onTranslateClick={handleTranslateClick} />
        );
        await userEvent.click(screen.getByTitle('Translate'));
        expect(handleTranslateClick).toHaveBeenCalled();
    });

    it('does not render WritingAssistantIcon if onWritingAssistantClick is not provided', () => {
        render(<FeatureBadge {...defaultProps} />);
        expect(screen.queryByTitle('Writing Assistant')).not.toBeInTheDocument();
    });

    it('does not render TranslateIcon if onTranslateClick is not provided', () => {
        render(<FeatureBadge {...defaultProps} />);
        expect(screen.queryByTitle('Translate')).not.toBeInTheDocument();
    });
});
