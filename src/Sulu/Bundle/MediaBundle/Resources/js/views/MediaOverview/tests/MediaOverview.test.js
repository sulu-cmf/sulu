/* eslint-disable flowtype/require-valid-file-annotation */
import React, {default as mockReact} from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {mount, render} from 'enzyme';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';
import MediaCardOverviewAdapter from '../../../containers/List/adapters/MediaCardOverviewAdapter';

jest.mock(
    'react-dropzone',
    () => mockReact.forwardRef(({children}, ref) => children({getInputProps: jest.fn(), getRootProps: jest.fn(), ref}))
);

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/CKEditor5', () => jest.fn(() => null));
jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        destroy: jest.fn(),
    })),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey, observableOptions) {
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
            thumbnails,
        },
        {
            id: 2,
            title: 'Title 1',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
    ];

    this.observableOptions = observableOptions;
    this.loading = false;
    this.pageCount = 3;
    this.moveSelection = jest.fn();
    this.reload = jest.fn();
    this.filterOptions = {
        get: jest.fn().mockReturnValue({}),
    };
    this.active = {
        get: jest.fn(),
    };
    this.sortColumn = {
        get: jest.fn(),
    };
    this.sortOrder = {
        get: jest.fn(),
    };
    this.searchTerm = {
        get: jest.fn(),
    };
    this.limit = {
        get: jest.fn().mockReturnValue(10),
    };
    this.setLimit = jest.fn();
    this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
        ? collectionData
        : mediaData;
    this.selections = [];
    this.selectionIds = [];
    this.getPage = jest.fn().mockReturnValue(2);
    this.getSchema = jest.fn().mockReturnValue({
        title: {},
        description: {},
    });
    this.destroy = jest.fn();
    this.sendRequest = jest.fn();
    this.clearSelection = jest.fn();
    this.clear = jest.fn();
    this.updateLoadingStrategy = jest.fn();
    this.updateStructureStrategy = jest.fn();
    this.sort = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.id = 1;

        mockExtendObservable(this, {
            loading: false,
            data: {
                id: 1,
                locked: false,
                _embedded: {
                    parent: {
                        id: 1,
                    },
                },
                _permissions: {},
            },
        });
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn(),
    };
});

jest.mock('sulu-admin-bundle/containers/SingleListOverlay/SingleListOverlay', () => jest.fn(() => null));

beforeEach(() => {
    jest.resetModules();

    const listAdapterRegistry = require('sulu-admin-bundle/containers/List/registries/listAdapterRegistry');

    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
        'table': require('sulu-admin-bundle/containers/List/adapters/TableAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

test('Render a simple MediaOverview', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = {
        attributes: {},
        bind: jest.fn(),
        route: {
            options: {
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    };

    const mediaOverview = render(<MediaOverview router={router} />);
    expect(mediaOverview).toMatchSnapshot();
});

test('Destroy all stores on unmount', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = {
        attributes: {},
        bind: jest.fn(),
        route: {
            options: {
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    };

    const mediaOverview = mount(<MediaOverview router={router} />);
    const mediaOverviewInstance = mediaOverview.instance();
    const collectionPage = router.bind.mock.calls[0][1];
    const mediaPage = router.bind.mock.calls[1][1];
    const locale = router.bind.mock.calls[2][1];
    const collectionLimit = router.bind.mock.calls[5][1];
    const mediaFilter = router.bind.mock.calls[6][1];
    const mediaLimit = router.bind.mock.calls[7][1];
    const mediaSortColumn = router.bind.mock.calls[8][1];
    const mediaSortOrder = router.bind.mock.calls[9][1];

    expect(mediaOverviewInstance.collectionListStore.sort).toBeCalledWith('title', 'asc');
    expect(collectionPage.get()).toBe(undefined);
    expect(mediaPage.get()).toBe(1);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('collectionPage', collectionPage, 1);
    expect(router.bind).toBeCalledWith('mediaPage', mediaPage, 1);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('collectionLimit', collectionLimit, 10);
    expect(router.bind).toBeCalledWith('mediaFilter', mediaFilter, {});
    expect(router.bind).toBeCalledWith('mediaLimit', mediaLimit, 10);
    expect(router.bind).toBeCalledWith('mediaSortColumn', mediaSortColumn);
    expect(router.bind).toBeCalledWith('mediaSortOrder', mediaSortOrder);

    mediaOverview.unmount();
    expect(mediaOverviewInstance.mediaListStore.destroy).toBeCalled();
    expect(mediaOverviewInstance.collectionListStore.destroy).toBeCalled();
    expect(mediaOverviewInstance.collectionStore.resourceStore.destroy).toBeCalled();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).at(0).instance();
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview);
    toolbarConfig.backButton.onClick();
    expect(mediaOverview.mediaListStore.clear).toBeCalled();
    expect(mediaOverview.mediaListStore.clearSelection).toBeCalled();
    expect(mediaOverview.collectionListStore.clear).toBeCalled();
    expect(mediaOverview.collectionListStore.clearSelection).toBeCalled();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {
        'collectionPage': '1',
        'id': 1,
        'locale': 'de',
    });
});

test('Router navigate should be called when a media was clicked', () => {
    const MediaOverview = require('../MediaOverview').default;
    const locale = 'de';
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [locale],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
        navigate: jest.fn(),
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().locale.set(locale);

    mediaOverview.find('.media').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith(
        'sulu_media.form.details',
        {'id': 1, locale}
    );
});

test('The collectionId should be update along with the content when a collection was clicked', () => {
    const MediaOverview = require('../MediaOverview').default;
    const locale = 'de';
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [locale],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
        navigate: jest.fn(),
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().locale.set(locale);
    mediaOverview.instance().mediaPage.set(3);
    mediaOverview.instance().collectionPage.set(2);
    mediaOverview.instance().collectionId.set(4);

    mediaOverview.find('Folder').at(0).simulate('click');

    expect(mediaOverview.instance().collectionId.get()).toEqual(1);
    expect(mediaOverview.instance().collectionPage.get()).toEqual(1);
    expect(mediaOverview.instance().mediaPage.get()).toEqual(1);
    expect(mediaOverview.instance().mediaListStore.clearSelection).toBeCalled();
    expect(mediaOverview.instance().mediaListStore.clear).toBeCalled();
    expect(mediaOverview.instance().collectionListStore.clearSelection).toBeCalled();
    expect(mediaOverview.instance().collectionListStore.clear).toBeCalled();
});

test('Delete overlay should be shown when delete button is clicked', () => {
    function getDeleteItem() {
        return toolbarFunction.call(mediaOverview.instance()).items
            .find((item) => item.label === 'sulu_admin.delete_selected');
    }

    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    };

    const mediaOverview = mount(<MediaOverview router={router} />);
    const mediaListStore = mediaOverview.instance().mediaListStore;
    mediaListStore.selectionIds.push(1, 4, 6);

    mediaOverview.update();
    expect(mediaOverview.find('List').at(1).find('Dialog[title="sulu_admin.delete_warning_title"]').at(0).prop('open'))
        .toEqual(false);

    getDeleteItem().onClick();
    mediaOverview.update();
    expect(mediaOverview.find('List').at(1).find('Dialog[title="sulu_admin.delete_warning_title"]').at(0).prop('open'))
        .toEqual(true);
});

test('Upload button should be disabled if collection is loading', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).at(0).instance();
    mediaOverview.locale.set('de');
    mediaOverview.collectionId.set(4);

    mediaOverview.collectionStore.resourceStore.loading = true;
    expect(toolbarFunction.call(mediaOverview).items[0].label).toEqual('sulu_media.upload_file');
    expect(toolbarFunction.call(mediaOverview).items[0].disabled).toBeTruthy();

    mediaOverview.collectionStore.resourceStore.loading = false;
    expect(toolbarFunction.call(mediaOverview).items[0].disabled).toBeFalsy();
});

test('Upload overlay should be opened and closed as it requests', () => {
    const MediaOverview = require('../MediaOverview').default;

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };

    const mediaOverview = mount(<MediaOverview router={router} />);

    expect(mediaOverview.find('MediaCollection').prop('uploadOverlayOpen')).toEqual(false);
    mediaOverview.find('MediaCollection').prop('onUploadOverlayOpen')();
    mediaOverview.update();
    expect(mediaOverview.find('MediaCollection').prop('uploadOverlayOpen')).toEqual(true);
    mediaOverview.find('MediaCollection').prop('onUploadOverlayClose')();
    mediaOverview.update();
    expect(mediaOverview.find('MediaCollection').prop('uploadOverlayOpen')).toEqual(false);
});

test('Toolbar buttons should disappear when permissions are missing', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: false,
                    delete: false,
                    edit: false,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).at(0).instance();
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(0);
});

test('Toolbar buttons should disappear when permissions are missing on current collection', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().collectionId.set(4);
    mediaOverview.instance().locale.set('de');

    mediaOverview.instance().collectionStore.resourceStore.data = {
        _permissions: {add: false, delete: false, edit: false},
    };

    expect(toolbarFunction.call(mediaOverview.instance()).items).toHaveLength(0);
});

test('Move button should be disabled if nothing is selected', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).at(0).instance();
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    expect(toolbarFunction.call(mediaOverview).items[2].disabled).toEqual(true);
    expect(toolbarFunction.call(mediaOverview).items[2].label).toEqual('sulu_admin.move_selected');

    mediaOverview.mediaListStore.selectionIds.push(8);
    expect(toolbarFunction.call(mediaOverview).items[2].disabled).toEqual(false);
});

test('Upload and move button should disappear if collection is locked', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).at(0).instance();
    mediaOverview.collectionId.set(4);
    mediaOverview.locale.set('de');

    mediaOverview.collectionStore.resourceStore.data.locked = false;
    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(3);
    expect(toolbarFunction.call(mediaOverview).items[0].label).toEqual('sulu_media.upload_file');
    expect(toolbarFunction.call(mediaOverview).items[2].label).toEqual('sulu_admin.move_selected');

    mediaOverview.collectionStore.resourceStore.data.locked = true;
    expect(toolbarFunction.call(mediaOverview).items).toHaveLength(1);
    expect(toolbarFunction.call(mediaOverview).items[0].label).not.toEqual('sulu_media.upload_file');
    expect(toolbarFunction.call(mediaOverview).items[0].label).not.toEqual('sulu_media.move_selected');
});

test('Move overlay should disappear when overlay is closed', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().collectionId.set(4);
    mediaOverview.instance().locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview.instance());

    expect(toolbarConfig.items[2].label).toEqual('sulu_admin.move_selected');
    toolbarConfig.items[2].onClick();
    mediaOverview.update();
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('listKey'))
        .toEqual('collections');
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('resourceKey'))
        .toEqual('collections');
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('open')).toEqual(true);

    mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('onClose')();
    mediaOverview.update();
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('open')).toEqual(false);
});

test('Media should be moved when overlay is confirmed', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().collectionId.set(4);
    mediaOverview.instance().locale.set('de');
    const movePromise = Promise.resolve();
    mediaOverview.instance().mediaListStore.moveSelection.mockReturnValue(movePromise);

    const toolbarConfig = toolbarFunction.call(mediaOverview.instance());

    expect(toolbarConfig.items[2].label).toEqual('sulu_admin.move_selected');
    toolbarConfig.items[2].onClick();
    mediaOverview.update();
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('resourceKey'))
        .toEqual('collections');
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('confirmLoading'))
        .toEqual(false);
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('open')).toEqual(true);

    mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('onConfirm')({id: 8});
    mediaOverview.update();
    expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('confirmLoading'))
        .toEqual(true);

    expect(mediaOverview.instance().mediaListStore.moveSelection).toBeCalledWith(8);

    return movePromise.then(() => {
        mediaOverview.update();
        expect(mediaOverview.instance().collectionListStore.reload).toHaveBeenCalledTimes(1);
        expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('open'))
            .toEqual(false);
        expect(mediaOverview.find(SingleListOverlay).find('[title="sulu_media.move_media"]').prop('confirmLoading'))
            .toEqual(false);
    });
});

test('Should show generic error if upload of multiple files fails in MediaCollection', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().collectionId.set(4);
    mediaOverview.instance().locale.set('de');

    expect(toolbarFunction.call(mediaOverview.instance()).errors).toEqual([]);

    mediaOverview.find('MediaCollection').props().onUploadError(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );

    expect(toolbarFunction.call(mediaOverview.instance()).errors).toEqual(['sulu_media.upload_server_error']);
});

test('Should show error message from serve if upload of a single files fails in MediaCollection', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaOverview);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
                permissions: {
                    add: true,
                    delete: true,
                    edit: true,
                },
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />);
    mediaOverview.instance().collectionId.set(4);
    mediaOverview.instance().locale.set('de');

    expect(toolbarFunction.call(mediaOverview.instance()).errors).toEqual([]);

    mediaOverview.find('MediaCollection').props().onUploadError(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );

    expect(toolbarFunction.call(mediaOverview.instance()).errors).toEqual(
        ['The uploaded file exceeds the configured maximum filesize.']
    );
});
