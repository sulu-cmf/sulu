// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Toggler from '../Toggler';

test('Render disabled toggler', () => {
    const {container} = render(
        <Toggler disabled={true} label="Disabled Toggler" onClick={jest.fn()} value={false} />
    );
    expect(container).toMatchSnapshot();
});

test('Render loading toggler', () => {
    const {container} = render(
        <Toggler label="Disabled Toggler" loading={true} onClick={jest.fn()} value={false} />
    );
    expect(container).toMatchSnapshot();
});

test('Render toggler with skin', () => {
    const {container} = render(
        <Toggler label="Dark Toggler" onClick={jest.fn()} skin="dark" value={false} />
    );
    expect(container).toMatchSnapshot();
});

test('Render with active toggler', () => {
    const {container} = render(
        <Toggler label="Active Toggler" onClick={jest.fn()} value={true} />
    );
    expect(container).toMatchSnapshot();
});

test('Call onClick handler when item was clicked', () => {
    const clickSpy = jest.fn();
    render(<Toggler label="Click Toggler" onClick={clickSpy} value={false} />);

    fireEvent.click(screen.queryByRole('button'));

    expect(clickSpy).toBeCalled();
});

test('Call onClick handler when toggler was changed', () => {
    const clickSpy = jest.fn();
    render(<Toggler label="Click Toggler" onClick={clickSpy} value={false} />);

    fireEvent.click(screen.queryByRole('checkbox'));

    expect(clickSpy).toBeCalled();
});
