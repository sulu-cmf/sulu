// @flow
import {createEvent, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import DisplayValue from '../DisplayValue';

test('The component should render a CroppedText if value of children prop is a string', () => {
    const {container} = render(<DisplayValue onClick={jest.fn()}>My value</DisplayValue>);
    expect(container).toMatchSnapshot();
});

test('The component should directly render given children if value of children prop contains another component', () => {
    const {container} = render(<DisplayValue onClick={jest.fn()}>Some <b>bold</b> text</DisplayValue>);
    expect(container).toMatchSnapshot();
});

test('The component should render with the flat skin', () => {
    const {container} = render(<DisplayValue onClick={jest.fn()} skin="flat">My value</DisplayValue>);
    expect(container).toMatchSnapshot();
});

test('The component should render with the dark skin', () => {
    const {container} = render(<DisplayValue onClick={jest.fn()} skin="dark">My value</DisplayValue>);
    expect(container).toMatchSnapshot();
});

test('The component should render with an icon', () => {
    const {container} = render(<DisplayValue icon="su-plus" onClick={jest.fn()}>My value</DisplayValue>);
    expect(container).toMatchSnapshot();
});

test('The component should render when disabled', () => {
    const {container} = render(<DisplayValue disabled={true} onClick={jest.fn()}>My value</DisplayValue>);
    expect(container).toMatchSnapshot();
});

test('A click on the component should fire the callback and prevent the default', () => {
    const clickSpy = jest.fn();

    render(<DisplayValue onClick={clickSpy}>My value</DisplayValue>);
    const display = screen.queryByRole('button');
    const event = createEvent.click(display);

    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent(display, event);

    expect(clickSpy).toBeCalled();
    expect(event.defaultPrevented).toBe(true);
});

test('A click on the component should not fire the callback when disabled', () => {
    const clickSpy = jest.fn();

    render(<DisplayValue disabled={true} onClick={clickSpy}>My value</DisplayValue>);
    const display = screen.queryByRole('button');

    return userEvent.click(display).then(() => {
        expect(clickSpy).not.toBeCalled();
    });
});

test('The component should use the CroppedText component to cut long texts', () => {
    const {container} = render(
        <DisplayValue onClick={jest.fn()}>This value should be wrapped in a CroppedText component</DisplayValue>
    );

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.croppedText')).toBeInTheDocument();
});
