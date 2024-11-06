// @flow
import React from 'react';
import {mount} from 'enzyme';
import metadataStore from '../stores/metadataStore';
import GhostDialog from '../GhostDialog';
import SingleSelect from '../fields/SingleSelect';
import Input from '../fields/Input';
import fieldRegistry from '../registries/fieldRegistry';

fieldRegistry.add('single_select', SingleSelect);
fieldRegistry.add('text_line', Input);

const FORM = {
    locale: {
        label: 'Sprache wählen',
        disabledCondition: null,
        visibleCondition: null,
        description: '',
        type: 'single_select',
        colSpan: 6,
        options: {
            default_value: {
                name: 'default_value',
                type: null,
                value: 'de',
                title: null,
                placeholder: null,
                infoText: null,
            },
            values: {
                name: 'values',
                type: 'collection',
                value: [
                    {
                        name: 'de',
                        type: null,
                        value: 'de',
                        title: 'de',
                        placeholder: null,
                        infoText: null,
                    },
                    {
                        name: 'en',
                        type: null,
                        value: 'en',
                        title: 'en',
                        placeholder: null,
                        infoText: null,
                    },
                ],
                title: null,
                placeholder: null,
                infoText: null,
            },
        },
        types: [],
        defaultType: null,
        required: true,
        spaceAfter: null,
        minOccurs: null,
        maxOccurs: null,
        onInvalid: null,
        tags: [],
    },
};

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve(FORM)),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Should render a Dialog', (resolve) => {
    const ghostDialog = mount(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={jest.fn()} open={true} />
    );
    setTimeout(() => {
        expect(ghostDialog.render()).toMatchSnapshot();

        resolve();
    }, 1);
});

test('Should call onCancel callback if user chooses not to copy content', () => {
    const cancelSpy = jest.fn();
    const ghostDialog = mount(
        <GhostDialog locales={['en', 'de']} onCancel={cancelSpy} onConfirm={jest.fn()} open={true} />
    );

    ghostDialog.find('Button[skin="secondary"]').simulate('click');

    expect(cancelSpy).toBeCalledWith();
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content', (resolve) => {
    const confirmSpy = jest.fn();
    const ghostDialog = mount(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    setTimeout(() => {
        ghostDialog.update();

        ghostDialog.find('SingleSelect').at(0).prop('onChange')('de');
        ghostDialog.find('Button[skin="primary"]').at(0).simulate('click');

        expect(confirmSpy).toBeCalledWith('de', {});

        resolve();
    }, 1);
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content (with additional fields)', (resolve) => { // eslint-disable-line max-len
    const formMetadata = {
        ...FORM,
        title: {
            label: 'Test',
            disabledCondition: null,
            visibleCondition: null,
            description: '',
            type: 'text_line',
            colSpan: 6,
        },
    };
    metadataStore.getSchema.mockReturnValue(Promise.resolve(formMetadata));

    const confirmSpy = jest.fn();
    const ghostDialog = mount(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    setTimeout(() => {
        ghostDialog.update();

        ghostDialog.find('Input').at(0).prop('onChange')('Test 123');
        ghostDialog.find('SingleSelect').at(0).prop('onChange')('de');
        ghostDialog.find('Button[skin="primary"]').at(0).simulate('click');

        expect(confirmSpy).toBeCalledWith('de', {
            title: 'Test 123',
        });

        resolve();
    }, 1);
});
