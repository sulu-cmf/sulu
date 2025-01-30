// @flow
import {defaultWebspace} from 'sulu-admin-bundle/utils/TestHelper';
import webspaceStore from '../webspaceStore';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

beforeEach(() => {
    webspaceStore.setWebspaces([]);
});

test('Has webspace', () => {
    const webspace1 = {
        ...defaultWebspace,
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspace2 = {
        ...defaultWebspace,
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.hasWebspace('sulu')).toEqual(true);
    expect(webspaceStore.hasWebspace('sulu_blog')).toEqual(true);
    expect(webspaceStore.hasWebspace('not_existing')).toEqual(false);
});

test('Load granted webspaces', () => {
    const webspace1 = {
        ...defaultWebspace,
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspace2 = {
        ...defaultWebspace,
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.grantedWebspaces).toEqual([webspace1]);
});

test('Load webspace with given key', () => {
    const webspace1 = {
        ...defaultWebspace,
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspace2 = {
        ...defaultWebspace,
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.getWebspace('sulu')).toEqual(webspace1);
});

test('Get granted webspaces', () => {
    const webspace1 = {
        ...defaultWebspace,
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspace2 = {
        ...defaultWebspace,
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.grantedWebspaces).toEqual([webspace1]);
});

test('Get webspace with given key', () => {
    const webspace1 = {
        ...defaultWebspace,
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspace2 = {
        ...defaultWebspace,
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        resourceLocatorStrategy: {inputType: 'leaf'},
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.getWebspace('sulu')).toEqual(webspace1);
});
