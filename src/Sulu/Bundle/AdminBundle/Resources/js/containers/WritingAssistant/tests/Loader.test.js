// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import Loader from '../Loader';
import loaderStyles from '../loader.scss';
import messageStyles from '../message.scss';

describe('Loader Component', () => {
    const defaultProps = {
        commandTitle: 'Test Command',
        expert: 'Test Expert',
    };

    test('renders the commandTitle and expert', () => {
        render(<Loader {...defaultProps} />);

        expect(screen.getByText(defaultProps.commandTitle)).toBeInTheDocument();
        expect(screen.getByText(defaultProps.expert)).toBeInTheDocument();
    });

    test('renders the correct number of skeleton loaders', () => {
        render(<Loader {...defaultProps} />);

        const skeletonLoaders = screen.getAllByText((content, element) =>
            element.classList.contains(loaderStyles.skeletonLoader)
        );

        expect(skeletonLoaders).toHaveLength(6);
    });

    test('applies the correct classes to the skeleton loaders', () => {
        render(<Loader {...defaultProps} />);

        const shortLoaders = screen.getAllByText((content, element) =>
            element.classList.contains(loaderStyles.short)
        );

        expect(shortLoaders).toHaveLength(2);
    });

    test('applies the correct classes to the command and expert elements', () => {
        render(<Loader {...defaultProps} />);

        const commandElement = screen.getByText(defaultProps.commandTitle).closest(`.${messageStyles.command}`);
        const expertElement = screen.getByText(defaultProps.expert).closest(`.${messageStyles.expert}`);

        expect(commandElement).toHaveClass(messageStyles.command);
        expect(expertElement).toHaveClass(messageStyles.expert);
    });
});
