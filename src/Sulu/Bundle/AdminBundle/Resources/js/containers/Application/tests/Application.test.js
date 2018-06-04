//@flow
import React from 'react';
import {render, mount} from 'enzyme';
import Application from '../Application';
import Router from '../../../services/Router';

jest.mock('../../../services/Router', () => function() {});

const mockInitializerInitialized = jest.fn();
const mockInitializerLoading = jest.fn();
const mockInitializedTranslationsLocale = jest.fn();

jest.mock('../../../services/Initializer', () => {
    return new class {
        get loading() {
            return mockInitializerLoading();
        }

        get initialized() {
            return mockInitializerInitialized();
        }

        get initializedTranslationsLocale() {
            return mockInitializedTranslationsLocale();
        }
    };
});

const mockUserStoreLoggedIn = jest.fn();
const mockUserStoreContact = jest.fn();
const mockUserStoreUser = jest.fn();

jest.mock('../../../stores/UserStore', () => {
    return new class {
        get loggedIn() {
            return mockUserStoreLoggedIn();
        }

        get user() {
            return mockUserStoreUser();
        }

        get contact() {
            return mockUserStoreContact();
        }
    };
});

jest.mock('../../ViewRenderer', () => function Test(props) {
    return (
        <div>
            <h1>Test</h1>
            <h2>{props.router.route.view}</h2>
        </div>
    );
});

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

beforeEach(() => {
    mockInitializerInitialized.mockReturnValue(true);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');

    mockUserStoreLoggedIn.mockReturnValue(true);
    mockUserStoreContact.mockReturnValue({
        fullName: 'Hikaru Sulu',
    });
    mockUserStoreUser.mockReturnValue({
        id: 99,
        username: 'test',
    });
});

test('Application should render login with loader', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(true);
    mockInitializedTranslationsLocale.mockReturnValue(null);
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    const application = mount(<Application router={router} />);
    expect(application.render()).toMatchSnapshot();
});

test('Application should render login when user is not logged in', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    const application = mount(<Application router={router} />);

    expect(application.render()).toMatchSnapshot();
});

test('Application should not fail if current route does not exist', () => {
    const router = new Router({});
    const view = render(<Application router={router} />);

    expect(view).toMatchSnapshot();
});

test('Application should render based on current route', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = render(<Application router={router} />);

    expect(view).toMatchSnapshot();
});

test('Application should render opened navigation', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = mount(<Application router={router} />);
    view.find('Button[icon="su-bars"]').simulate('click');

    expect(view).toMatchSnapshot();
});
