// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextEditor from '../../fields/TextEditor';
import userStore from '../../../../stores/userStore';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../stores/userStore', () => ({}));

test('Pass props correctly to TextEditor', () => {
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
        adapter: 'ckeditor5',
        locale,
        onBlur: finishSpy,
        onChange: changeSpy,
        options,
        value: 'xyz',
        disabled: true,
    }));
});

test('Pass content locale from user to TextEditor if form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

    // $FlowFixMe
    userStore.contentLocale = 'de';

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

    expect(textEditor.find('TextEditor').props().locale).toBeDefined();
    expect(textEditor.find('TextEditor').props().locale.get()).toEqual('de');
});

test('Call onFocus when editor get focus', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const focusSpy = jest.fn();

    const textEditor = shallow(
        <TextEditor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFocus={focusSpy}
        />
    );

    const target = new EventTarget();

    textEditor.find('TextEditor').props().onFocus({target});

    expect(focusSpy).toBeCalledWith(target);
});
