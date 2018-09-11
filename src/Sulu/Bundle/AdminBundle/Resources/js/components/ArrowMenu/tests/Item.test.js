// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Item from '../Item';

test('Render default Item', () => {
    expect(render(<Item value="test">Test Item</Item>)).toMatchSnapshot();
});

test('Render active Item', () => {
    expect(render(<Item active={true} icon="fa-home" value="house">My House</Item>)).toMatchSnapshot();
});

test('Clicking the left and right button inside the header should call the right handler', () => {
    const clickHandler = jest.fn();

    const item = mount(
        <Item active={true} icon="fa-home" onClick={clickHandler} value="house">My House</Item>
    );

    item.simulate('click');
    expect(clickHandler).toBeCalledWith('house');
});
