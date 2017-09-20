/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import ResourceRequester from '../ResourceRequester';
import Requester from '../../Requester/Requester';

jest.mock('../../Requester/Requester', () => ({
    get: jest.fn(),
    delete: jest.fn(),
}));

jest.mock('../../../stores/ResourceMetadataStore', () => ({
    getBaseUrl: jest.fn().mockImplementation((resourceKey) => {
        switch (resourceKey) {
            case 'snippets':
                return '/snippets';
            case 'contacts':
                return '/contacts';
        }
    }),
}));

test('Should send a list get request and return the promise', () => {
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.getList('snippets');
    expect(result).toBe(promise);
});

test('Should send a list get request to the correct URL', () => {
    ResourceRequester.getList('snippets');
    expect(Requester.get).toBeCalledWith('/snippets?flat=true&page=1&limit=10');

    ResourceRequester.getList('contacts');
    expect(Requester.get).toBeCalledWith('/contacts?flat=true&page=1&limit=10');
});

test('Should send a list get request to the correct URL with page and limit parameters', () => {
    ResourceRequester.getList('snippets', {
        page: 3,
        limit: 20,
    });
    expect(Requester.get).toBeCalledWith('/snippets?flat=true&page=3&limit=20');

    ResourceRequester.getList('snippets', {
        page: 5,
    });
    expect(Requester.get).toBeCalledWith('/snippets?flat=true&page=5&limit=10');

    ResourceRequester.getList('snippets', {
        limit: 5,
    });
    expect(Requester.get).toBeCalledWith('/snippets?flat=true&page=1&limit=5');
});

test('Should send a delete request and return the promise', () => {
    const promise = {};
    Requester.delete.mockReturnValue(promise);
    const result = ResourceRequester.delete('snippets');
    expect(result).toBe(promise);
});

test('Should send a delete request to the correct URL', () => {
    ResourceRequester.delete('snippets', 5);
    expect(Requester.delete).toBeCalledWith('/snippets/5');

    ResourceRequester.delete('contacts', 9);
    expect(Requester.delete).toBeCalledWith('/contacts/9');
});
