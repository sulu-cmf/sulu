// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import securityContextStore from '../../../stores/securityContextStore';
import RolePermissions from '../RolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../stores/securityContextStore', () => ({
    getAvailableActions: jest.fn(),
}));

beforeEach(() => {
    RolePermissions.resourceKeyMapping = {snippets: 'sulu.global.snippets'};
});

test('Render matrix with correct all values selected if not given', () => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Administrator', permissions: []},
                    {id: 2, name: 'Account Manager', permissions: []},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);

    const value = {};
    const rolePermissions = mount(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={value} />);

    expect(rolePermissions.render()).toMatchSnapshot();

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(rolePermissions.render()).toMatchSnapshot();
    });
});

test('Render matrix with correct given values', () => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Admin'},
                    {id: 2, name: 'Contact Manager'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);

    const value = {
        '1': {
            view: true,
            add: false,
            edit: true,
            delete: true,
        },
        '2': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    };
    const rolePermissions = mount(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={value} />);

    expect(rolePermissions.render()).toMatchSnapshot();

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(rolePermissions.render()).toMatchSnapshot();
    });
});

test('Render matrix with correct default values from roles', () => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {
                        id: 1,
                        name: 'Admin',
                        permissions: [
                            {
                                context: 'sulu.global.snippets',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: true,
                                    delete: false,
                                    security: true,
                                },
                            },
                        ],
                    },
                    {
                        id: 2,
                        name: 'Contact Manager',
                        permissions: [
                            {
                                context: 'sulu.contact.people',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: true,
                                    delete: true,
                                    security: true,
                                },
                            },
                            {
                                context: 'sulu.global.snippets',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: false,
                                    delete: false,
                                    security: true,
                                },
                            },
                        ],
                    },
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);

    const rolePermissions = mount(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={{}} />);

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(rolePermissions.render()).toMatchSnapshot();
    });
});

test('Call onChange callback when value changes', () => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Administrator', permissions: []},
                    {id: 2, name: 'Account Manager', permissions: []},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);

    const value = {};
    const rolePermissions = shallow(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);

    expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets');

    return Promise.all([rolePromise]).then(() => {
        const newValue = {
            '1': {
                view: true,
                add: false,
                edit: true,
                delete: true,
            },
            '2': {
                view: true,
                add: true,
                edit: true,
                delete: false,
            },
        };
        rolePermissions.update();

        rolePermissions.find('Matrix').prop('onChange')(newValue);
        expect(changeSpy).toBeCalledWith(newValue);
    });
});
