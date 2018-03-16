// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import Assignment from '../../fields/Assignment';

test('Should pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const locale = observable.box('en');
    const value = [1, 6, 8];
    const fieldOptions = {
        displayProperties: ['id', 'title'],
        icon: '',
        label: 'Select snippets',
        overlayTitle: 'Snippets',
        resourceKey: 'snippets',
    };
    const assignment = shallow(
        <Assignment onChange={changeSpy} fieldOptions={fieldOptions} locale={locale} value={value} />
    );

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        displayProperties: ['id', 'title'],
        label: 'Select snippets',
        locale,
        onChange: changeSpy,
        resourceKey: 'snippets',
        overlayTitle: 'Snippets',
        value,
    }));
});

test('Should pass empty array if value is not given', () => {
    const changeSpy = jest.fn();
    const fieldOptions = {
        resourceKey: 'pages',
    };
    const assignment = shallow(<Assignment onChange={changeSpy} fieldOptions={fieldOptions} value={undefined} />);

    expect(assignment.find('Assignment').props()).toEqual(expect.objectContaining({
        onChange: changeSpy,
        locale: undefined,
        resourceKey: 'pages',
        value: [],
    }));
});

test('Should throw an error if no fieldOptions are passed', () => {
    expect(() => shallow(<Assignment onChange={jest.fn()} value={undefined} />)).toThrowError(/"resourceKey"/);
});

test('Should throw an error if no resourceKey is passed in fieldOptions', () => {
    expect(() => shallow(<Assignment onChange={jest.fn()} fieldOptions={{}} value={undefined} />))
        .toThrowError(/"resourceKey"/);
});
