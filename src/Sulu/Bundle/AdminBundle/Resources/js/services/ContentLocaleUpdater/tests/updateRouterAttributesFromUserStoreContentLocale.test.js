// @flow
import updateRouterAttributesFromUserStoreContentLocale from '../updateRouterAttributesFromUserStoreContentLocale';
import userStore from '../../../stores/userStore/userStore';
import type {Route} from '../../Router';

jest.mock('../../../stores/userStore/userStore', () => {
    return {
        contentLocale: undefined,
    };
});

const UNLOCALIZED_ROUTE: Route = {
    attributeDefaults: {},
    children: [],
    name: 'unlocalized_route',
    options: {},
    parent: undefined,
    path: '/example',
    rerenderAttributes: [],
    type: '',
};

const LOCALIZED_ROUTE: Route = {
    attributeDefaults: {},
    children: [],
    name: 'localized_route',
    options: {
        locales: ['en', 'de', 'fr'],
    },
    parent: undefined,
    path: '/:locale/example',
    rerenderAttributes: [],
    type: '',
};

test('Should not update locale attribute when route is not localized', () => {
    userStore.contentLocale = 'fr';
    const attributes = updateRouterAttributesFromUserStoreContentLocale(UNLOCALIZED_ROUTE, {});

    expect(attributes.locale).toBe(undefined);
});

test('Should not update locale attribute when locale was explicit set', () => {
    userStore.contentLocale = 'fr';
    const attributes = updateRouterAttributesFromUserStoreContentLocale(LOCALIZED_ROUTE, {
        locale: 'de',
    });

    expect(attributes.locale).toBe('de');
});

test('Should not update locale attribute when user store locale is available for current route', () => {
    userStore.contentLocale = 'ru';
    const attributes = updateRouterAttributesFromUserStoreContentLocale(LOCALIZED_ROUTE, {});

    expect(attributes.locale).toBe(undefined);
});

test('Should update locale attribute from user store when not explicit set', () => {
    userStore.contentLocale = 'fr';
    const attributes = updateRouterAttributesFromUserStoreContentLocale(LOCALIZED_ROUTE, {});

    expect(attributes.locale).toBe('fr');
});
