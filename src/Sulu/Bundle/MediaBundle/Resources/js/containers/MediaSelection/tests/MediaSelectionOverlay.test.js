/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import pretty from 'pretty';
import React from 'react';
import datagridAdapterRegistry from 'sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaCardSelectionAdapter from '../../Datagrid/adapters/MediaCardSelectionAdapter';
import MediaSelectionOverlay from '../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Form: require('sulu-admin-bundle/containers/Form').default,
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey) {
            const {extendObservable} = require.requireActual('mobx');
            const COLLECTIONS_RESOURCE_KEY = 'collections';

            const collectionData = [
                {
                    id: 1,
                    title: 'Title 1',
                    objectCount: 1,
                    description: 'Description 1',
                },
                {
                    id: 2,
                    title: 'Title 2',
                    objectCount: 0,
                    description: 'Description 2',
                },
            ];

            const thumbnails = {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            };

            const mediaData = [
                {
                    id: 1,
                    title: 'Title 1',
                    mimeType: 'image/png',
                    size: 12345,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails: thumbnails,
                },
                {
                    id: 2,
                    title: 'Title 2',
                    mimeType: 'image/jpeg',
                    size: 54321,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails: thumbnails,
                },
            ];
            extendObservable(this, {
                selections: [],
            });
            this.loading = false;
            this.pageCount = 3;
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.getPage = jest.fn().mockReturnValue(2);
            this.getFields = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.updateStrategies = jest.fn();
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.setAppendRequestData = jest.fn();
            this.deselectEntirePage = jest.fn();
            this.select = jest.fn();
            this.getSchema = jest.fn().mockReturnValue({});
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        has: jest.fn(),
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.loading = false;
        this.id = 1;
        this.data = {
            id: 1,
        };
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
        }
    },
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.reset_selection':
                return 'Reset fields';
            case 'sulu_media.select_media':
                return 'Select media';
            case 'sulu_admin.confirm':
                return 'Confirm';
        }
    },
}));

beforeEach(() => {
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
        'media_card_selection': MediaCardSelectionAdapter,
    });
});

test('Render an open MediaSelectionOverlay', () => {
    const locale = observable();
    const body = document.body;
    mount(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).render();

    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('Should instantiate the needed stores when the overlay opens', () => {
    const mediaResourceKey = 'media';
    const collectionResourceKey = 'collections';
    const locale = observable();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).instance();

    expect(mediaSelectionOverlayInstance.mediaPage.get()).toBe(1);
    expect(mediaSelectionOverlayInstance.collectionPage.get()).toBe(1);

    expect(DatagridStore.mock.calls[0][0]).toBe(mediaResourceKey);
    expect(DatagridStore.mock.calls[0][1].locale).toBe(locale);
    expect(DatagridStore.mock.calls[0][1].page.get()).toBe(1);
    expect(DatagridStore.mock.calls[0][2].fields).toEqual([
        'id',
        'type',
        'name',
        'size',
        'title',
        'mimeType',
        'subVersion',
        'thumbnails',
    ].join(','));

    expect(DatagridStore.mock.calls[1][0]).toBe(collectionResourceKey);
    expect(DatagridStore.mock.calls[1][1].locale).toBe(locale);
    expect(DatagridStore.mock.calls[1][1].page.get()).toBe(1);
});

test('Should add and remove media ids', () => {
    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const locale = observable();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).instance();

    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([]);

    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [1],
        removed: [],
    }));
    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [2],
        removed: [],
    }));
    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ]);

    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [],
        removed: [2],
    }));
    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ]);
});

test('Should reset the selection array when the "Reset Selection" button was clicked', () => {
    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const locale = observable();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).instance();

    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [1],
        removed: [],
    }));
    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [2],
        removed: [],
    }));
    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ]);

    mediaSelectionOverlayInstance.handleSelectionReset();
    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([]);
    expect(mediaSelectionOverlayInstance.mediaDatagridStore.deselectEntirePage).toBeCalled();
});

test('Should destroy the stores and cleanup all states when the overlay is closed', () => {
    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const locale = observable();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).instance();

    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [1],
        removed: [],
    }));
    mediaSelectionOverlayInstance.handleMediaSelectionChanges(observable({
        added: [2],
        removed: [],
    }));
    mediaSelectionOverlayInstance.setCollectionId(1);

    expect(mediaSelectionOverlayInstance.collectionId).toBe(1);
    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ]);

    mediaSelectionOverlayInstance.handleClose();
    expect(mediaSelectionOverlayInstance.collectionId).toBe(undefined);
    expect(mediaSelectionOverlayInstance.selectedMedia).toEqual([]);
    expect(mediaSelectionOverlayInstance.collectionStore.resourceStore.destroy).toBeCalled();
    expect(mediaSelectionOverlayInstance.mediaDatagridStore.destroy).toBeCalled();
    expect(mediaSelectionOverlayInstance.collectionDatagridStore.destroy).toBeCalled();
});
