/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import MediaCardOverviewAdapter from '../../../containers/Datagrid/adapters/MediaCardOverviewAdapter';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        withToolbar: jest.fn((Component) => Component),
        Form: require('sulu-admin-bundle/containers/Form').default,
        FormStore: jest.fn(),
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
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
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-100x100': 'http://lorempixel.com/100/100',
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
                    title: 'Title 1',
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
            this.getSchema = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.updateStrategies = jest.fn();
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.loading = false;
        this.id = 1;
        this.data = {
            id: 1,
            _embedded: {
                parent: {
                    id: 1,
                },
            },
        };
    }),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.all_media':
                return 'All Media';
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Downoad master file';
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

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        has: jest.fn(),
    };
});

beforeEach(() => {
    jest.resetModules();

    const datagridAdapterRegistry = require('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry');

    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

test('Render a simple MediaOverview', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = {
        bind: jest.fn(),
        attributes: {},
    };

    const mediaOverview = render(<MediaOverview router={router} />);
    expect(mediaOverview).toMatchSnapshot();
});

test('Unbind all query params and destroy all stores on unmount', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        attributes: {},
    };

    const mediaOverview = mount(<MediaOverview router={router} />);
    const mediaOverviewInstance = mediaOverview.instance();
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('collectionPage', page, '1');
    expect(router.bind).toBeCalledWith('locale', locale);

    mediaOverview.unmount();
    expect(mediaOverviewInstance.mediaDatagridStore.destroy).toBeCalled();
    expect(mediaOverviewInstance.collectionDatagridStore.destroy).toBeCalled();
    expect(mediaOverviewInstance.collectionStore.resourceStore.destroy).toBeCalled();
    expect(router.unbind).toBeCalledWith('collectionPage', page);
    expect(router.unbind).toBeCalledWith('locale', locale);
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).at(0).instance();
    mediaOverview.collectionId = 4;
    mediaOverview.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {
        'collectionPage': '1',
        'id': 1,
        'locale': 'de',
    });
});

test('Router navigate should be called when a collection or media was clicked', () => {
    const MediaOverview = require('../MediaOverview').default;
    const locale = 'de';
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [locale],
            },
        },
        attributes: {
            id: 4,
        },
        navigate: jest.fn(),
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().collectionId = 4;
    mediaOverview.instance().locale.set(locale);

    mediaOverview.find('Folder').at(1).simulate('click');
    expect(router.navigate).toBeCalledWith(
        'sulu_media.overview',
        {'collectionPage': '1', 'id': 2, 'locale': locale}
    );

    mediaOverview.find('.media').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith(
        'sulu_media.form.detail',
        {'id': 1, 'locale': locale}
    );
});
