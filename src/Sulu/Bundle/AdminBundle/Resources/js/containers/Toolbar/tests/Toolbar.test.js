// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Toolbar from '../Toolbar';
import toolbarStorePool from '../stores/ToolbarStorePool';

let toolbarStoreMock = {};

jest.mock('../stores/ToolbarStorePool', () => ({
    createStore: jest.fn(),
    getStore: jest.fn(),
    hasStore: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    toolbarStoreMock = {
        errors: [],
        showSuccess: false,
        hasBackButtonConfig: jest.fn(),
        getBackButtonConfig: jest.fn(),
        hasItemsConfig: jest.fn(),
        getItemsConfig: jest.fn(),
        hasIconsConfig: jest.fn(),
        getIconsConfig: jest.fn(),
        hasLocaleConfig: jest.fn(),
        getLocaleConfig: jest.fn(),
    };
});

test('Render the items and icons from the ToolbarStore', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.hasItemsConfig.mockReturnValue(true);
    toolbarStoreMock.hasIconsConfig.mockReturnValue(true);
    toolbarStoreMock.hasLocaleConfig.mockReturnValue(false);
    toolbarStoreMock.hasBackButtonConfig.mockReturnValue(false);

    toolbarStoreMock.getIconsConfig.mockReturnValue(
        [
            <p key={1}>Test1</p>,
            <p key={2}>Test2</p>,
        ]
    );

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Delete',
                disabled: true,
                icon: 'fa-trash-o',
            },
            {
                type: 'dropdown',
                label: 'Save',
                icon: 'fa-floppy-more',
                options: [
                    {
                        label: 'Save as draft',
                        onClick: () => {},
                    },
                    {
                        label: 'Publish',
                        onClick: () => {},
                    },
                    {
                        label: 'Save and publish',
                        onClick: () => {},
                    },
                ],
            },
            {
                type: 'toggler',
                label: 'Toggler',
                onClick: () => {},
                value: true,
            },
        ]
    );

    expect(render(<Toolbar storeKey={storeKey} />)).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);
});

test('Render the items as disabled if one is loading', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.hasItemsConfig.mockReturnValue(true);
    toolbarStoreMock.hasIconsConfig.mockReturnValue(false);
    toolbarStoreMock.hasLocaleConfig.mockReturnValue(false);
    toolbarStoreMock.hasBackButtonConfig.mockReturnValue(true);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Add',
                icon: 'fa-add-o',
                disabled: false,
            },
            {
                type: 'button',
                label: 'Delete',
                icon: 'fa-trash-o',
                loading: true,
            },
        ]
    );

    const view = shallow(<Toolbar storeKey={storeKey} />);
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);

    const buttons = view.find('Button');
    expect(buttons.at(0).prop('disabled')).toBe(true);
    expect(buttons.at(1).prop('disabled')).toBe(true);
    expect(buttons.at(2).prop('disabled')).toBe(true);
});

test('Show success message for some time', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.hasItemsConfig.mockReturnValue(true);
    toolbarStoreMock.hasIconsConfig.mockReturnValue(false);
    toolbarStoreMock.hasLocaleConfig.mockReturnValue(false);
    toolbarStoreMock.hasBackButtonConfig.mockReturnValue(true);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.getItemsConfig.mockReturnValue([]);
    toolbarStoreMock.showSuccess = true;

    const view = shallow(<Toolbar storeKey={storeKey} />);

    expect(view.find('Snackbar[type="success"]')).toHaveLength(1);
    expect(view.find('Snackbar[type="success"]').prop('type')).toEqual('success');
});

test('Click on the success message should open the navigation', () => {
    const storeKey = 'testStore';
    const navigationButtonClickSpy = jest.fn();

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.hasItemsConfig.mockReturnValue(true);
    toolbarStoreMock.hasIconsConfig.mockReturnValue(false);
    toolbarStoreMock.hasLocaleConfig.mockReturnValue(false);
    toolbarStoreMock.hasBackButtonConfig.mockReturnValue(true);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.getItemsConfig.mockReturnValue([]);
    toolbarStoreMock.showSuccess = true;

    const view = shallow(<Toolbar onNavigationButtonClick={navigationButtonClickSpy} storeKey={storeKey} />);

    view.find('Snackbar[type="success"]').simulate('click');

    expect(navigationButtonClickSpy).toBeCalledWith();
});

test('Remove last error if close button on snackbar is clicked', () => {
    const storeKey = 'testStore';

    // $FlowFixMe
    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.hasItemsConfig.mockReturnValue(true);
    toolbarStoreMock.hasIconsConfig.mockReturnValue(false);
    toolbarStoreMock.hasLocaleConfig.mockReturnValue(false);
    toolbarStoreMock.hasBackButtonConfig.mockReturnValue(true);
    toolbarStoreMock.getBackButtonConfig.mockReturnValue({});
    toolbarStoreMock.errors.push({code: 100, message: 'Something went wrong'});

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Add',
                icon: 'fa-add-o',
                disabled: false,
            },
        ]
    );

    const view = shallow(<Toolbar storeKey={storeKey} />);

    expect(view.find('Snackbar[type="error"]')).toHaveLength(1);

    expect(toolbarStoreMock.errors).toHaveLength(1);
    view.find('Snackbar[type="error"]').simulate('closeClick');
    expect(toolbarStoreMock.errors).toHaveLength(0);
});
