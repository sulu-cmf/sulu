// @flow
import {mount} from 'enzyme';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import CopyLocaleToolbarAction from '../../toolbarActions/CopyLocaleToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.observableOptions = observableOptions;
    this.data = {};
    this.locale = {
        get: jest.fn(),
    };
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    post: jest.fn(),
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
}));

function createCopyLocaleToolbarAction(locales, options = {}) {
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new CopyLocaleToolbarAction(formStore, form, router, locales, options);
}

test('Return enabled item config', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 5;

    expect(copyLocaleToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        label: 'sulu_admin.copy_locale',
    }));
});

test('Return no item config if condition is not met', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(
        ['en', 'de'],
        {display_condition: '_permission.edit'}
    );

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    expect(toolbarItemConfig).toEqual(undefined);
});

test('Return item config if condition is met', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(
        ['en', 'de'],
        {display_condition: '_permission.edit'}
    );

    copyLocaleToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: true};
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 1;

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig)
        .toEqual(expect.objectContaining({disabled: false, label: 'sulu_admin.copy_locale', type: 'button'}));
});

test('Return disabled item config if an add form is shown', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de']);

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig.disabled).toEqual(true);
});

test('Return no dialog if no id is set', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(copyLocaleToolbarAction.getNode()).toEqual(null);
});

test('Throw error if no locale is given', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceStore.locale = undefined;

    expect(() => copyLocaleToolbarAction.getNode()).toThrow('locale');
});

test('Throw error if no available locales are given', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction();
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');

    expect(() => copyLocaleToolbarAction.getNode()).toThrow('locales');
});

test('Close dialog when cancel button of dialog is clicked', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    copyLocaleToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = mount(copyLocaleToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(copyLocaleToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Dialog').prop('onCancel')();
    expect(copyLocaleToolbarAction.form.showSuccessSnackbar).not.toBeCalledWith();
    element = mount(copyLocaleToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Close dialog and show success message when onClose from CopyLocaleDialog is called with true', () => {
    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de', 'fr']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceKey = 'snippets';
    const locale = copyLocaleToolbarAction.resourceFormStore.resourceStore.locale;
    // $FlowFixMe
    locale.get.mockReturnValue('en');
    copyLocaleToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = mount(copyLocaleToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(copyLocaleToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Checkbox[value="de"]').prop('onChange')(true, 'de');
    element.find('Checkbox[value="fr"]').prop('onChange')(true, 'fr');
    element.prop('onConfirm')();
    expect(ResourceRequester.post).toBeCalledWith(
        'snippets',
        undefined,
        {action: 'copy-locale', dest: ['de', 'fr'], id: 3, locale, webspace: 'sulu_io'}
    );

    return postPromise.then(() => {
        expect(copyLocaleToolbarAction.form.showSuccessSnackbar).toBeCalledWith();
        element = mount(copyLocaleToolbarAction.getNode()).at(0);
        expect(element.instance().props).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});
