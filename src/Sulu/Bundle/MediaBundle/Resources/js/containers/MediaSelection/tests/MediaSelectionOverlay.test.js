/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import pretty from 'pretty';
import React from 'react';
import datagridAdapterRegistry from 'sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaCardSelectionAdapter from '../../Datagrid/adapters/MediaCardSelectionAdapter';
import CollectionStore from '../../../stores/CollectionStore';
import MediaSelectionOverlay from '../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey) {
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
                'sulu-260x': 'http://lorempixel.com/260/100',
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

            this.loading = false;
            this.pageCount = 3;
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.selections = [];
            this.getPage = jest.fn().mockReturnValue(2);
            this.getFields = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.setAppendRequestData = jest.fn();
            this.deselectEntirePage = jest.fn();
            this.getSchema = jest.fn().mockReturnValue({});
        }),
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

jest.mock('../../../stores/CollectionStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.all_media':
                return 'All Media';
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Download master file';
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

jest.mock('sulu-admin-bundle/services/Translator', () => ({
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

jest.mock('sulu-admin-bundle/services', () => ({
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
        'folder': {
            Adapter: require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
            paginationType: 'default',
        },
        'media_card_selection': {
            Adapter: MediaCardSelectionAdapter,
            paginationType: 'infiniteScroll',
        },
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
