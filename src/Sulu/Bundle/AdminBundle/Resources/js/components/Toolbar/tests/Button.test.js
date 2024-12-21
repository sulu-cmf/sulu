// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Button from '../Button';

test('Render button', () => {
    const {container} = render(<Button onClick={jest.fn()}>Click</Button>);
    expect(container).toMatchSnapshot();
});

test('Render success button', () => {
    const {container} = render(<Button onClick={jest.fn()} success={true}>Click</Button>);
    expect(container).toMatchSnapshot();
});

test('Render primary button', () => {
    const {container} = render(<Button onClick={jest.fn()} primary={true}>Click</Button>);
    expect(container).toMatchSnapshot();
});

test('Render primary success button', () => {
    const {container} = render(<Button onClick={jest.fn()} primary={true} success={true}>Click</Button>);
    expect(container).toMatchSnapshot();
});

test('Render loading button', () => {
    const {container} = render(<Button loading={true} onClick={jest.fn()}>Click</Button>);
    expect(container).toMatchSnapshot();
});

test('Render button with value', () => {
    const {container} = render(<Button label="Click" onClick={jest.fn()} />);
    expect(container).toMatchSnapshot();
});

test('Render button without text', () => {
    const {container} = render(<Button onClick={jest.fn()} showText={false} />);
    expect(container).toMatchSnapshot();
});

test('Render disabled button', () => {
    const {container} = render(<Button disabled={true} onClick={jest.fn()} />);
    expect(container).toMatchSnapshot();
});

test('Click on button fires onClick callback', async() => {
    const clickSpy = jest.fn();
    render(<Button onClick={clickSpy} />);
    const button = screen.queryByRole('button');

    await userEvent.click(button);

    expect(clickSpy).toBeCalled();
});

test('Render button with dropdown indicator', () => {
    const {container} = render(<Button hasOptions={true} onClick={jest.fn()} />);
    expect(container).toMatchSnapshot();
});

test('Render button with a different size', () => {
    const {container} = render(<Button onClick={jest.fn()} size="small" />);
    expect(container).toMatchSnapshot();
});

test('Render button with a prepended icon', () => {
    const {container} = render(<Button icon="fa-trash-o" onClick={jest.fn()} />);
    expect(container).toMatchSnapshot();
});

test('Render an active button', () => {
    const {container} = render(<Button active={true} onClick={jest.fn()} />);
    expect(container).toMatchSnapshot();
});

test('Click on button does not fire onClick callback if button is disabled', () => {
    const clickSpy = jest.fn();
    render(<Button disabled={true} onClick={clickSpy} />);

    const button = screen.queryByRole('button');

    return userEvent.click(button).then(() => {
        expect(clickSpy).toHaveBeenCalledTimes(0);
    });
});
