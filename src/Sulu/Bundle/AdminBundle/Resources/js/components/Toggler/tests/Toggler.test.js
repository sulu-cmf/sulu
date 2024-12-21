// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import Toggler from '../Toggler';

test('The component should render in the default state', () => {
    const {container} = render(
        <Toggler
            checked={true}
            name="my-name"
            onChange={jest.fn()}
            value="my-value"
        >
            Label
        </Toggler>);

    expect(container).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const {container} = render(
        <Toggler
            checked={true}
            disabled={true}
            name="my-name"
            onChange={jest.fn()}
            value="my-value"
        >
            Label
        </Toggler>);

    expect(container).toMatchSnapshot();
});

test('The component pass the props correctly to the generic checkbox', () => {
    const onChange = jest.fn();
    render(
        <Toggler
            checked={true}
            disabled={true}
            name="my-name"
            onChange={onChange}
            value="my-value"
        >
            My label
        </Toggler>
    );

    const togglerInput = screen.queryByDisplayValue('my-value');
    expect(togglerInput.value).toBe('my-value');
    expect(togglerInput.name).toBe('my-name');
    expect(togglerInput).toBeChecked();
    expect(togglerInput).toBeDisabled();
    expect(screen.getByText('My label')).toBeInTheDocument();
});
