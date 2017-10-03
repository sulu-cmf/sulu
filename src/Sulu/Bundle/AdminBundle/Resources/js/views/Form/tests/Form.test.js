/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, shallow} from 'enzyme';

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.delete':
                return 'Delete';
        }
    },
}));

jest.mock('../../../containers/Form/stores/FieldStore', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
}));

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.save':
                return 'Save';
        }
    },
}));

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn(),
}));

beforeEach(() => {
    jest.resetModules();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);
    form.formStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form);
    toolbarConfig.backButton.onClick();
    expect(router.navigate).toBeCalledWith('test_route', {}, {locale: 'de'});
});

test('Should not render back button when no editLink is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should change locale in form store via locale chooser', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);
    form.formStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(form);
    toolbarConfig.locale.onChange('en');
    expect(form.formStore.locale.get()).toBe('en');
});

test('Should show locales from router options in toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {
                locales: ['en', 'de'],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should not show a locale chooser if no locales are passed in router options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.locale).toBe(undefined);
});

test('Should initialize the FormStore with a schema', () => {
    const Form = require('../Form').default;

    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                locales: [],
            },
        },
        attributes: {
            id: 12,
        },
    };

    const form = mount(<Form router={router} />).get(0);
    expect(form.formStore.resourceKey).toBe('snippets');
    expect(form.formStore.id).toBe(12);
    expect(form.formStore.data).toEqual({
        title: null,
        slogan: null,
    });
});

test('Should render save button disabled only if form is not dirty', () => {
    function getSaveItem() {
        return toolbarFunction.call(form).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        bindQuery: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);

    expect(getSaveItem().disabled).toBe(true);

    form.formStore.dirty = true;
    expect(getSaveItem().disabled).toBe(false);
});

test('Should save form when submitted', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const Form = require('../Form').default;

    const router = {
        bindQuery: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                locales: [],
            },
        },
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form router={router} />);
    const formStore = form.get(0).formStore;
    formStore.locale.set('en');
    formStore.data = {value: 'Value'};
    formStore.loading = false;
    form.find('Form').at(1).simulate('submit');

    expect(ResourceRequester.put).toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en'});
});

test('Should pass store, schema and onSubmit handler to FormContainer', () => {
    const Form = require('../Form').default;

    const router = {
        bindQuery: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };

    const form = shallow(<Form router={router} />);
    const formContainer = form.find('Form');

    expect(formContainer.prop('store').data).toEqual({
        title: null,
        slogan: null,
    });
    expect(formContainer.prop('onSubmit')).toBeInstanceOf(Function);
    expect(Object.keys(formContainer.prop('schema'))).toEqual(['title', 'slogan']);
});

test('Should render save button loading only if form is not saving', () => {
    function getSaveItem() {
        return toolbarFunction.call(form).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        bindQuery: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} />).get(0);

    expect(getSaveItem().loading).toBe(false);

    form.formStore.saving = true;
    expect(getSaveItem().loading).toBe(true);
});

test('Should unbind the query parameter and destroy the store on unmount', () => {
    const Form = require('../Form').default;
    const router = {
        bindQuery: jest.fn(),
        unbindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                locales: [],
            },
        },
        attributes: {},
    };

    const form = mount(<Form router={router} />);

    expect(router.bindQuery).toBeCalledWith('locale', form.find('Form').at(1).prop('store').locale);

    form.unmount();
    expect(router.unbindQuery).toBeCalledWith('locale');
});
