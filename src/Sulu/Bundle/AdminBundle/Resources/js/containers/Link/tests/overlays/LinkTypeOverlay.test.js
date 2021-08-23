// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import LinkTypeOverlay from '../../overlays/LinkTypeOverlay';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../services/ResourceRequester/registries/resourceRouteRegistry', () => ({
    getDetailUrl: jest.fn(),
    getListUrl: jest.fn(),
}));

test('Render overlay with minimal config', () => {
    const response = {
        ok: true,
        json: jest.fn(),
    };
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay without options', () => {
    expect(() => shallow(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={undefined}
        />
    )).toThrow('The LinkTypeOverlay needs some options in order to work!');
});

test('Render overlay with anchor enabled', () => {
    const response = {
        ok: true,
        json: jest.fn(),
    };
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onAnchorChange={jest.fn()}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with target enabled', () => {
    const response = {
        ok: true,
        json: jest.fn(),
    };
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTargetChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with title enabled', () => {
    const response = {
        ok: true,
        json: jest.fn(),
    };
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});
