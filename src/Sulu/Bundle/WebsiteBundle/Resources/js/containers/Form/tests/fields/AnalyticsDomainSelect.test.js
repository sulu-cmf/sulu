// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import AnalyticsDomainSelect from '../../fields/AnalyticsDomainSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
    }),
    ResourceFormStore: jest.fn(function(resourceStore, formKey, options) {
        this.options = options;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        getWebspace: jest.fn(),
    },
}));

test('Pass correct props to MultiSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        urls: [
            {url: '{host}/{localization}'},
            {url: '{host}'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const analyticsDomainSelect = shallow(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['{host}']}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');

    expect(analyticsDomainSelect.find('MultiSelect').prop('disabled')).toEqual(true);
    expect(analyticsDomainSelect.find('MultiSelect').prop('values')).toEqual(['{host}']);
    expect(analyticsDomainSelect.find('Option').at(0).prop('children')).toEqual('{host}/{localization}');
    expect(analyticsDomainSelect.find('Option').at(0).prop('value')).toEqual('{host}/{localization}');
    expect(analyticsDomainSelect.find('Option').at(1).prop('children')).toEqual('{host}');
    expect(analyticsDomainSelect.find('Option').at(1).prop('value')).toEqual('{host}');
});

test('Call onChange and onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        urls: [
            {url: '{host}/{localization}'},
            {url: '{host}'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const analyticsDomainSelect = shallow(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['{host}']}
        />
    );

    analyticsDomainSelect.find('MultiSelect').prop('onChange')(['{host}', '{host}/{localization}']);
    expect(changeSpy).toBeCalledWith(['{host}', '{host}/{localization}']);
    expect(finishSpy).toBeCalledWith();
});
