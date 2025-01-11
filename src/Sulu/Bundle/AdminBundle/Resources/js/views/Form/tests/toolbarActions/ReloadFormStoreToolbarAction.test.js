// @flow
import {mount} from 'enzyme';
import symfonyRouting from 'fos-jsrouting/router';
import ReloadFormStoreToolbarAction from '../../toolbarActions/ReloadFormStoreToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import Requester from '../../../../services/Requester';

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

jest.mock('fos-jsrouting/router', () => ({
    generate: jest.fn(),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        options = {};

        setMultiple = jest.fn();

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get data() {
            return this.resourceStore.data;
        }

        get locale() {
            return this.resourceStore.locale;
        }
    }
));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.route = {
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
    this.showSuccessSnackbar = jest.fn();
    this.errors = [];
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.locale = {
        get: jest.fn(),
    };
}));

function createReloadFormStoreToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });
    return new ReloadFormStoreToolbarAction(
        resourceFormStore,
        form,
        router,
        [],
        {
            icon: 'su-sync',
            route: 'test_route',
            dialogKey: 'test_dialog',
            dialogTitle: 'Test Dialog',
            dialogDescription: 'Test Description',
            label: 'Test Dialog',
            dialogOkText: 'OK',
            dialogCancelText: 'Cancel',
            ...options,
        },
        resourceStore
    );
}

test('Throw error if required options are missing', () => {
    expect(() => createReloadFormStoreToolbarAction({icon: undefined})).toThrow(/Missing required options/);
});

test('Return correct toolbar item config', () => {
    const action = createReloadFormStoreToolbarAction({label: 'Reload'});
    const config = action.getToolbarItemConfig();

    expect(config).toEqual({
        type: 'button',
        label: 'Reload',
        icon: 'su-sync',
        onClick: expect.any(Function),
    });
});

test('Open dialog on button click', () => {
    const action = createReloadFormStoreToolbarAction();
    const config = action.getToolbarItemConfig();

    config.onClick();

    expect(action.showDialog).toBe(true);
});

test('Close dialog on cancel', () => {
    const action = createReloadFormStoreToolbarAction();
    action.showDialog = true;

    const element = mount(action.getNode());
    element.find('Button[skin="secondary"]').simulate('click');

    expect(action.showDialog).toBe(false);
});

test('Fetch data on confirm', async() => {
    const action = createReloadFormStoreToolbarAction();
    action.showDialog = true;
    action.resourceFormStore.resourceStore.id = 5;
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.resourceFormStore.resourceStore.load = jest.fn();

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({});

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    expect(action.loading).toBe(true);

    await new Promise((resolve) => setTimeout(resolve));

    expect(Requester.post).toHaveBeenCalledWith('/test/5?locale=en');
    expect(action.resourceFormStore.resourceStore.load).toHaveBeenCalled();
    expect(action.loading).toBe(false);
    expect(action.showDialog).toBe(false);
});

test('Handle error on fetch', async() => {
    const action = createReloadFormStoreToolbarAction();
    action.showDialog = true;

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'error.message'});
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.loading).toBe(false);
    expect(action.showDialog).toBe(false);
    expect(action.form.errors).toContain('error.message');
});

test('Render dialog with correct props', () => {
    const action = createReloadFormStoreToolbarAction({
        dialogCancelText: 'Cancel Test',
        dialogOkText: 'OK Test',
    });
    action.showDialog = true;

    const element = mount(action.getNode());
    const dialog = element.find('Dialog');

    expect(dialog.prop('cancelText')).toBe('Cancel Test');
    expect(dialog.prop('confirmText')).toBe('OK Test');
    expect(dialog.prop('title')).toBe('Test Dialog');
    expect(dialog.prop('children')).toBe('Test Description');
});
