// @flow
import 'url-search-params-polyfill';
import {observable} from 'mobx';
import {resourceRouteRegistry, ResourceRequester} from 'sulu-admin-bundle/services';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        delete: jest.fn(),
    },
    resourceRouteRegistry: {
        getUrl: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.data = {id};
        this.locale = observableOptions ? observableOptions.locale : undefined;
        this.setMultiple = jest.fn();
        this.delete = jest.fn();
    }),
}));

test('Calling the "update" method should make a "POST" request to the media update api', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/media/1?action=new-version&locale=en');

    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(fileData);
    expect(resourceRouteRegistry.getUrl).toBeCalledWith(
        'detail',
        'media',
        {action: 'new-version', id: 1, locale: 'en'}
    );
    expect(openSpy).toBeCalledWith('POST', '/media/1?action=new-version&locale=en');
});

test('Promise returned by "update" method should be resolved if request is successful', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/media/1?action=new-version&locale=en');

    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''},
        observable.box('en')
    );
    mediaUploadStore.error = {detail: 'previous-upload-error'};
    const fileData = new File([''], 'fileName');

    const updatePromise = mediaUploadStore.update(fileData);

    window.XMLHttpRequest.mock.instances[0].onload({target: {status: 200, response: '{"title": "updated-title"}'}});

    expect(updatePromise).resolves.toEqual({title: 'updated-title'});

    return updatePromise.then(() => {
        expect(mediaUploadStore.uploading).toEqual(false);
        expect(mediaUploadStore.progress).toEqual(0);
        expect(mediaUploadStore.media).toEqual(
            {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'updated-title', thumbnails: {}, url: '', adminUrl: ''}
        );
        expect(mediaUploadStore.error).toEqual(undefined);
    });
});

test('Promise returned by "update" method should be rejected if request has error status', (done) => {
    resourceRouteRegistry.getUrl.mockReturnValue('/media/1?action=new-version&locale=en');

    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''},
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    const updatePromise = mediaUploadStore.update(fileData);

    window.XMLHttpRequest.mock.instances[0].onload({
        target: {
            status: 400,
            response: '{"code":5003,"message":"Bad Request"}',
        },
    });

    expect(updatePromise).rejects.toEqual({code: 5003, message: 'Bad Request'});

    // wait until rejection of updatePromise was handled by component with setTimeout
    setTimeout(() => {
        expect(mediaUploadStore.uploading).toEqual(false);
        expect(mediaUploadStore.progress).toEqual(0);
        expect(mediaUploadStore.media).toEqual(
            {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''}
        );
        expect(mediaUploadStore.error).toEqual({code: 5003, message: 'Bad Request'});

        done();
    });
});

test('Promise returned by "update" method should be rejected if request is not successful', (done) => {
    resourceRouteRegistry.getUrl.mockReturnValue('/media/1?action=new-version&locale=en');

    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''},
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    const updatePromise = mediaUploadStore.update(fileData);

    window.XMLHttpRequest.mock.instances[0].onerror({target: {status: 'network-error'}});

    expect(updatePromise).rejects.toEqual({status: 'network-error'});

    // wait until rejection of updatePromise was handled by component with setTimeout
    setTimeout(() => {
        expect(mediaUploadStore.uploading).toEqual(false);
        expect(mediaUploadStore.progress).toEqual(0);
        expect(mediaUploadStore.media).toEqual(
            {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''}
        );
        expect(mediaUploadStore.error).toEqual({status: 'network-error'});

        done();
    });
});

test('Calling the "create" method should make a "POST" request to the media update api', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/media?locale=en&collection=1');

    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    const createPromise = mediaUploadStore.create(1, fileData);

    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'media', {collection: 1, locale: 'en'});
    expect(openSpy).toBeCalledWith('POST', '/media?locale=en&collection=1');

    window.XMLHttpRequest.mock.instances[0].onload({target: {status: 200, response: '{"title": "test1"}'}});

    return createPromise.then(() => {
        expect(mediaUploadStore.media).toEqual({title: 'test1'});
    });
});

test('Calling "delete" method should call the "delete" method of the ResourceRequester', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 2,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    ResourceRequester.delete.mockReturnValue(Promise.resolve());

    const deletePromise = mediaUploadStore.delete();
    expect(ResourceRequester.delete).toBeCalledWith('media', {id: 2});

    return deletePromise.then(() => {
        expect(mediaUploadStore.media).toEqual(undefined);
    });
});

test('Calling "deletePreviewImage" method should call the "delete" method of the ResourceRequester', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 2,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const media = {
        id: 2,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {'50x50': 'image.jpg'},
        url: '',
        adminUrl: '',
    };
    ResourceRequester.delete.mockReturnValue(Promise.resolve(media));

    const deletePromise = mediaUploadStore.deletePreviewImage();
    expect(ResourceRequester.delete).toBeCalledWith('media_preview', {id: 2});

    return deletePromise.then(() => {
        expect(mediaUploadStore.media).toEqual(media);
    });
});

test('Calling the "updatePreviewImage" method should make a "POST" request to the preview media update api', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/media/1/preview?locale=en');

    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {
            id: 2,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    mediaUploadStore.updatePreviewImage(fileData);
    expect(resourceRouteRegistry.getUrl).toBeCalledWith(
        'detail',
        'media_preview',
        {id: 2, locale: 'en'}
    );
    expect(openSpy).toBeCalledWith('POST', '/media/1/preview?locale=en');
});

test('After the "update" call request was successful the progress will be reset', () => {
    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    const updatePromise = mediaUploadStore.update(fileData);
    mediaUploadStore.progress = 4;
    expect(mediaUploadStore.uploading).toEqual(true);

    window.XMLHttpRequest.mock.instances[0].onload({target: {status: 200, response: '{"title": "test1"}'}});

    return updatePromise.then(() => {
        expect(mediaUploadStore.uploading).toEqual(false);
        expect(mediaUploadStore.progress).toEqual(0);
        expect(mediaUploadStore.media).toEqual(
            {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test1', thumbnails: {}, url: '', adminUrl: ''}
        );
        expect(mediaUploadStore.error).toEqual(undefined);
    });
});

test('After the "updatePreviewImage" call request was successful the progress will be reset', () => {
    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    const updatePreviewPromise = mediaUploadStore.updatePreviewImage(fileData);
    mediaUploadStore.progress = 4;
    expect(mediaUploadStore.uploading).toEqual(true);

    const response = '{"id": 1, "mimeType": "image/jpeg", "title": "test", "thumbnails": {"50x50": "image.jpg"}, ' +
        '"url": "", "adminUrl": ""}';

    window.XMLHttpRequest.mock.instances[0].onload({target: {status: 200, response}});

    return updatePreviewPromise.then(() => {
        expect(mediaUploadStore.uploading).toEqual(false);
        expect(mediaUploadStore.progress).toEqual(0);
        expect(mediaUploadStore.media).toEqual({
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {'50x50': 'image.jpg'},
            url: '',
            adminUrl: '',
        });
        expect(mediaUploadStore.error).toEqual(undefined);
    });
});

test('Should return thumbnail path if available', () => {
    const thumbnailUrl = '/media/uploads/400x400/test.png';
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 2,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {
                'sulu-400x400-inset': thumbnailUrl,
            },
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    expect(mediaUploadStore.getThumbnail('sulu-400x400-inset')).toEqual(thumbnailUrl);
});

test('Should return undefined if thumbnail is not available yet', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    expect(mediaUploadStore.getThumbnail('100x100')).toEqual(undefined);
});

test('Should return the mime type of the media if available', () => {
    const mimeType = 'image/jpg';
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 2,
            locale: 'en',
            mimeType,
            title: 'test',
            thumbnails: {
            },
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    expect(mediaUploadStore.mimeType).toEqual(mimeType);
});

test('Should return undefined if the mime type is not available yet', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    expect(mediaUploadStore.mimeType).toEqual(undefined);
});

test('Should return downloadUrl if available', () => {
    const url = 'test.jpg';
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url,
            adminUrl: undefined,
        },
        observable.box('en')
    );

    expect(mediaUploadStore.downloadUrl).toEqual(url);
});

test('Should return adminUrl if available', () => {
    const url = 'test.jpg';
    const adminUrl = 'admin-test.jpg';
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url,
            adminUrl,
        },
        observable.box('en')
    );

    expect(mediaUploadStore.downloadUrl).toEqual(adminUrl);
});

test('Should return undefined if downloadUrl is not available', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    expect(mediaUploadStore.downloadUrl).toEqual(undefined);
});
