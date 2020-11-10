// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import Schedules from '../Schedules';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render Schedules', () => {
    const value = [
        {type: 'fixed'},
        {type: 'weekly'},
    ];

    expect(render(<Schedules onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Change value when BlockCollection adds a new block', () => {
    const value = [];
    const changeSpy = jest.fn();
    const schedules = mount(<Schedules onChange={changeSpy} value={value} />);

    schedules.find('BlockCollection Button[icon="su-plus"] button').simulate('click');
    expect(changeSpy).toBeCalledWith([{type: 'fixed'}]);
});

test('Change value when a FixedSchedule in the BlockCollection changes', () => {
    const value = [{type: 'weekly'}, {type: 'fixed'}];
    const changeSpy = jest.fn();
    const schedules = mount(<Schedules onChange={changeSpy} value={value} />);

    const date = new Date();
    schedules.find('FixedSchedule Field[label="sulu_admin.start"] DatePicker').prop('onChange')(date);

    expect(changeSpy).toBeCalledWith([{type: 'weekly'}, {type: 'fixed', start: date}]);
});

test('Change value when a WeeklySchedule in the BlockCollection changes', () => {
    const value = [{type: 'weekly'}, {type: 'fixed'}];
    const changeSpy = jest.fn();
    const schedules = mount(<Schedules onChange={changeSpy} value={value} />);

    const date = new Date();
    schedules.find('WeeklySchedule Field[label="sulu_admin.start"] DatePicker').prop('onChange')(date);

    expect(changeSpy).toBeCalledWith([{type: 'weekly', start: date}, {type: 'fixed'}]);
});
