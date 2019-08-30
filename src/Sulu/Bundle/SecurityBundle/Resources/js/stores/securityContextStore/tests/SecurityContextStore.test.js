// @flow
import {Requester} from 'sulu-admin-bundle/services';
import securityContextStore from '../securityContextStore';

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    get: jest.fn(),
}));

test('Load available actions for permissions with given keys', () => {
    securityContextStore.resourceKeyMapping = {
        'test': 'sulu.test',
    };

    Requester.get.mockReturnValue(Promise.resolve({
        'Sulu': {
            'Global': {
                'sulu.snippets': ['view', 'add'],
            },
            'Test': {
                'sulu.test': ['view', 'add', 'edit'],
            },
        },
    }));

    return securityContextStore.loadAvailableActions('test').then((actions) => {
        expect(actions).toEqual(['view', 'add', 'edit']);
    });
});

test('Load security contexts for entire system', () => {
    securityContextStore.resourceKeyMapping = {
        'test': 'sulu.test',
    };

    const suluSecurityContexts = {
        'Global': {
            'sulu.snippets': ['view', 'add'],
        },
        'Test': {
            'sulu.test': ['view', 'add', 'edit'],
        },
    };

    Requester.get.mockReturnValue(Promise.resolve({
        'Sulu': suluSecurityContexts,
    }));

    return securityContextStore.loadSecurityContextGroups('Sulu').then((securityContexts) => {
        expect(securityContexts).toEqual(suluSecurityContexts);
    });
});
