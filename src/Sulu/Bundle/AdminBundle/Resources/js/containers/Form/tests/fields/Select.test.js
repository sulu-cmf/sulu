// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Select from '../../fields/Select';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass props correctly to Select', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    const select = shallow(
        <Select
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={['test']}
        />
    );

    expect(select.prop('values')).toEqual(['test']);
    expect(select.prop('disabled')).toBe(true);
    expect(select.find('Option').at(0).props()).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(select.find('Option').at(1).props()).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        default_value: {
            value: {},
        },
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    expect(() => shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/"default_value"/);
});

test('Should throw an exception if value is of wrong type', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        values: {
            value: [
                {
                    value: [],
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    expect(() => shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/"values"/);
});

test('Should call onFinish callback on every onChange', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    const select = shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    select.simulate('change');

    expect(finishSpy).toBeCalledWith();
});

test('Set default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            value: null,
        },
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            value: ['mr'],
        },
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith(['mr']);
});

test('Set default value to a string "mr" should work', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            value: 'mr',
        },
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'mrs',
                    title: 'Miss',
                },
            ],
        },
    };
    shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith(['mr']);
});

test('Set default value to a number of 0 should work', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            value: 0,
        },
        values: {
            value: [
                {
                    value: 0,
                    title: 'Mister',
                },
                {
                    value: 1,
                    title: 'Miss',
                },
            ],
        },
    };
    shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith([0]);
});

test('Throw error if no schemaOptions are passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    expect(() => shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />)
    ) .toThrow(/"values"/);
});

test('Throw error if no value option is passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    expect(() => shallow(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />)
    ).toThrow(/"values"/);
});
