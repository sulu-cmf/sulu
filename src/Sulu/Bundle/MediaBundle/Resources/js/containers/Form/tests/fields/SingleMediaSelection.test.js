// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router from 'sulu-admin-bundle/services/Router';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import {observable} from 'mobx';
import SingleMediaSelectionComponent from '../../../SingleMediaSelection';
import SingleMediaSelection from '../../fields/SingleMediaSelection';

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(() => null));

test('Pass correct props to SingleMediaSelection component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const mediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            error={{keyword: 'mandatory', parameters: {}}}
            formInspector={formInspector}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(mediaSelection.find(SingleMediaSelectionComponent).props().disabled).toEqual(true);
    expect(mediaSelection.find(SingleMediaSelectionComponent).props().valid).toEqual(false);
    expect(mediaSelection.find(SingleMediaSelectionComponent).props().locale.get()).toEqual('en');
    expect(mediaSelection.find(SingleMediaSelectionComponent).props().value).toEqual({id: 33});
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    const mediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(mediaSelection.find(SingleMediaSelectionComponent).props().locale.get()).toEqual('userContentLocale');
});

test('Set types on SingleMediaSelectionComponent', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        types: {name: 'types', value: 'image,video'},
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const singleMediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaSelection.find(SingleMediaSelectionComponent).props().types).toEqual(['image', 'video']);
});

test('Set default display option if no value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        defaultDisplayOption: {
            name: 'defaultDisplayOption',
            value: 'left',
        },
        displayOptions: {
            name: 'displayOptions',
            value: [{name: 'left', value: 'true'}],
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith({displayOption: 'left', id: undefined});
});

test('Do not set default display option if value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        defaultDisplayOption: {
            name: 'defaultDisplayOption',
            value: 'left',
        },
        displayOptions: {
            name: 'displayOptions',
            value: [{name: 'left', value: 'true'}],
        },
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value={{displayOption: 'left', id: undefined}}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Should call onChange and onFinish if the selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const mediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{displayOption: undefined, id: 55}}
        />
    );

    mediaSelection.find(SingleMediaSelectionComponent).props().onChange({id: 44});

    expect(changeSpy).toBeCalledWith({id: 44});
    expect(finishSpy).toBeCalled();
});

test('Should call onItemClick if item is clicked', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const router = new Router();

    // $FlowFixMe
    SingleSelectionStore.mockImplementation(function() {
        this.item = {id: 6, locale: 'de', title: 'Test', mimeType: 'image/jpeg'};
    });

    const mediaSelection = mount(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            router={router}
            value={{displayOption: undefined, id: 55}}
        />
    );

    mediaSelection.find('SingleItemSelection .item').simulate('click');

    expect(router.navigate).toBeCalledWith('sulu_media.form', {id: 6, locale: 'de'});
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: true}}}
        />
    )).toThrow(/"displayOptions"/);
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: [{name: 'test', value: true}]}}}
        />
    )).toThrow(/"displayOptions"/);
});

test('Should throw an error if types schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: true}}}
        />
    )).toThrow(/"types"/);
});
