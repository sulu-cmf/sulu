// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextEditor from '../../fields/TextEditor';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass props correctly to TextEditor', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

    const textEditor = shallow(
        <TextEditor
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value="xyz"
        />
    );

    expect(textEditor.find('TextEditor').props()).toEqual(expect.objectContaining({
        adapter: 'ckeditor5',
        locale: undefined,
        onBlur: finishSpy,
        onChange: changeSpy,
        options,
        value: 'xyz',
        disabled: true,
    }));
});

test('Pass locale prop correctly to TextEditor', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

    const locale = observable.box('en');
    // $FlowFixMe
    formInspector.locale = locale;

    const textEditor = shallow(
        <TextEditor
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value="xyz"
        />
    );

    expect(textEditor.find('TextEditor').props()).toEqual(expect.objectContaining({
        locale,
    }));
});
