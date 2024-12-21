// @flow
import 'url-search-params-polyfill';
import ResourceRequester from '../ResourceRequester';
import Requester from '../../Requester/Requester';
import resourceRouteRegistry from '../registries/resourceRouteRegistry';

jest.mock('../../Requester/Requester', () => ({
    get: jest.fn(),
    patch: jest.fn(),
    put: jest.fn(),
    post: jest.fn(),
    delete: jest.fn(),
}));

jest.mock('../registries/resourceRouteRegistry', () => ({
    getUrl: jest.fn(),
}));

test('Should send a get request and return the promise', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5');
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.get('snippets', {id: 5});
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {id: 5});
    expect(Requester.get).toBeCalledWith('/snippets/5');
    expect(result).toBe(promise);
});

test('Should send a get request without an ID and return the promise', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets');
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.get('snippets');
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {});
    expect(Requester.get).toBeCalledWith('/snippets');
    expect(result).toBe(promise);
});

test('Should send a get request with passed options as query parameters', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5?locale=en&action=publish');
    const options = {id: 5, locale: 'en', action: 'publish'};
    ResourceRequester.get('snippets', options);
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', options);
    expect(Requester.get).toBeCalledWith('/snippets/5?locale=en&action=publish');
});

test('Should send a list get request and return the promise', () => {
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.getList('snippets', {page: 1, limit: 10});
    expect(result).toBe(promise);
});

test('Should send a list get request to the correct URL with page and limit parameters', () => {
    resourceRouteRegistry.getUrl.mockImplementation((type, resourceKey, {flat, limit, page}) => {
        return '/snippets?page=' + page + '&limit=' + limit + '&flat=' + flat;
    });

    ResourceRequester.getList('snippets', {
        limit: 20,
        page: 3,
    });
    expect(Requester.get).toHaveBeenLastCalledWith('/snippets?page=3&limit=20&flat=true');

    ResourceRequester.getList('snippets', {
        limit: 10,
        page: 5,
    });
    expect(Requester.get).toHaveBeenLastCalledWith('/snippets?page=5&limit=10&flat=true');

    ResourceRequester.getList('snippets', {
        limit: 5,
        page: 1,
    });
    expect(Requester.get).toHaveBeenLastCalledWith('/snippets?page=1&limit=5&flat=true');

    ResourceRequester.getList('snippets', {
        flat: false,
        limit: 5,
        page: 1,
    });
    expect(Requester.get).toHaveBeenLastCalledWith('/snippets?page=1&limit=5&flat=true');
});

test('Should send a put request and return the promise', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5');
    const promise = {};
    const data = {title: 'Title'};
    Requester.put.mockReturnValue(promise);
    const result = ResourceRequester.put('snippets', data, {id: 5});
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {id: 5});
    expect(Requester.put).toBeCalledWith('/snippets/5', data);
    expect(result).toBe(promise);
});

test('Should send a put request with passed options as query parameters', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5?locale=en&action=publish');
    const data = {slogan: 'Slogan'};
    const options = {action: 'publish', id: 5, locale: 'en'};
    Requester.put.mockReturnValue({});
    ResourceRequester.put('snippets', data, options);
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {action: 'publish', id: 5, locale: 'en'});
    expect(Requester.put).toBeCalledWith('/snippets/5?locale=en&action=publish', data);
});

test('Should send a patch request and return the promise', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5');
    const promise = {};
    const data = {title: 'Title'};
    Requester.patch.mockReturnValue(promise);
    const result = ResourceRequester.patch('snippets', data, {id: 5});
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {id: 5});
    expect(Requester.patch).toBeCalledWith('/snippets/5', data);
    expect(result).toBe(promise);
});

test('Should send a patch request with passed options as query parameters', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5?locale=en&action=publish');
    const data = {slogan: 'Slogan'};
    const options = {action: 'publish', id: 5, locale: 'en'};
    Requester.patch.mockReturnValue({});
    ResourceRequester.patch('snippets', data, options);
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {action: 'publish', id: 5, locale: 'en'});
    expect(Requester.patch).toBeCalledWith('/snippets/5?locale=en&action=publish', data);
});

test('Should send a delete request and return the promise', () => {
    const promise = {};
    Requester.delete.mockReturnValue(promise);
    const result = ResourceRequester.delete('snippets', {id: 1});
    expect(result).toBe(promise);
});

test('Should send a delete request to the correct URL', () => {
    resourceRouteRegistry.getUrl
        .mockImplementation((type, resourceKey, {id}) => '/' + resourceKey + '/' + id);

    ResourceRequester.delete('snippets', {id: 5});
    expect(Requester.delete).toBeCalledWith('/snippets/5');

    ResourceRequester.delete('contacts', {id: 9});
    expect(Requester.delete).toBeCalledWith('/contacts/9');
});

test('Should send a delete request with passed options as query parameters', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets/5?locale=en&webspace=sulu');
    const options = {id: 5, locale: 'en', webspace: 'sulu'};
    Requester.delete.mockReturnValue({});
    ResourceRequester.delete('snippets', options);
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {id: 5, locale: 'en', webspace: 'sulu'});
    expect(Requester.delete).toBeCalledWith('/snippets/5?locale=en&webspace=sulu');
});

test('Should send a delete request and return the promise', () => {
    const promise = {};
    Requester.delete.mockReturnValue(promise);
    const result = ResourceRequester.deleteList('snippets', {ids: [3]});
    expect(result).toBe(promise);
});

test('Should send a collection delete request to the correct URL', () => {
    resourceRouteRegistry.getUrl
        .mockImplementation((type, resourceKey, {ids}) => '/' + resourceKey + '?ids=' + ids.join(','));

    ResourceRequester.deleteList('snippets', {ids: [1, 2, 3]});
    expect(Requester.delete).toBeCalledWith('/snippets?ids=1,2,3');

    ResourceRequester.deleteList('contacts', {ids: [4, 5, 6]});
    expect(Requester.delete).toBeCalledWith('/contacts?ids=4,5,6');
});

test('Should send a post request and return the promise', () => {
    resourceRouteRegistry.getUrl.mockReturnValue('/snippets');
    const promise = {};
    Requester.post.mockReturnValue(promise);
    const result = ResourceRequester.post('snippets', {});
    expect(resourceRouteRegistry.getUrl).toBeCalledWith('detail', 'snippets', {});
    expect(Requester.post).toBeCalledWith('/snippets', {});
    expect(result).toBe(promise);
});
