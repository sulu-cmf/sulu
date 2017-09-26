/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import React from 'react';
import Action from '../Action';

test('The component should render', () => {
    const onClick = () => {};
    const afterAction = () => {};
    const action = render(<Action onClick={onClick} afterAction={afterAction} value="my-option">My action</Action>);
    expect(action).toMatchSnapshot();
});

test('The component should call the callbacks after a click', () => {
    const onClick = jest.fn();
    const afterAction = jest.fn();
    const action = shallow(<Action onClick={onClick} afterAction={afterAction} value="my-option">My action</Action>);
    action.find('button').simulate('click');
    expect(onClick).toBeCalled();
    expect(afterAction).toBeCalled();
});
