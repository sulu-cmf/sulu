/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import datagridAdapterRegistry from 'sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry';
import MediaSelection from '../MediaSelection';
import MediaCardSelectionAdapter from '../../Datagrid/adapters/MediaCardSelectionAdapter';
import MediaSelectionStore from '../stores/MediaSelectionStore';

jest.mock('../stores/MediaSelectionStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers', () => {
    return {
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
            this.init = jest.fn();
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
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

jest.mock('sulu-admin-bundle/utils', () => ({
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
            case 'sulu_media.media_selected_singular':
                return 'media element selected';
            case 'sulu_media.media_selected_plural':
                return 'media elements selected';
            case 'sulu_media.reset_selection':
                return 'Reset fields';
            case 'sulu_media.select_media':
                return 'Select media';
            case 'sulu_admin.confirm':
                return 'Confirm';
        }
    },
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

beforeEach(() => {
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
        'media_card_selection': MediaCardSelectionAdapter,
    });
});

test('Render a MediaSelection field', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [
            {
                id: 1,
                title: 'Media 1',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnail: 'http://lorempixel.com/25/25',
            },
        ];
        this.selectedMediaIds = [1, 2, 3];
    });

    expect(render(
        <MediaSelection />
    )).toMatchSnapshot();
});

test('The MediaSelection should have 3 child-items', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [
            {
                id: 1,
                title: 'Media 1',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnail: 'http://lorempixel.com/25/25',
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnail: 'http://lorempixel.com/25/25',
            },
        ];
        this.selectedMediaIds = [1, 2, 3];
    });

    const mediaSelection = shallow(
        <MediaSelection />
    );

    expect(mediaSelection.find('Item').length).toBe(3);
});

test('Clicking on the "add media" button should open up an overlay', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
    });

    const body = document.body;
    const mediaSelection = mount(<MediaSelection />);

    mediaSelection.find('.button.left').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('Should remove media from the selection', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.removeById = jest.fn();
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.handleRemove(1);
    expect(changeSpy).toBeCalled();
    expect(mediaSelectionInstance.mediaSelectionStore.removeById).toBeCalledWith(1);
});

test('Should move media inside the selection', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.move = jest.fn();
    });

    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.handleSorted(1, 3);
    expect(changeSpy).toBeCalled();
    expect(mediaSelectionInstance.mediaSelectionStore.move).toBeCalledWith(1, 3);
});

test('Should add the selected medias to the selection store on confirm', () => {
    MediaSelectionStore.mockImplementation(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.add = jest.fn();
    });

    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const changeSpy = jest.fn();
    const mediaSelectionInstance = shallow(<MediaSelection onChange={changeSpy} />).instance();

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleOverlayConfirm([
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
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].id).toBe(1);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].title).toBe('Title 1');
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].id).toBe(2);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].title).toBe('Title 2');
    expect(changeSpy).toBeCalled();
    expect(mediaSelectionInstance.overlayOpen).toBe(false);
});
