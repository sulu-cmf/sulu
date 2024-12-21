// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import searchStore from '../../stores/searchStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

beforeEach(() => {
    searchStore.search(undefined);
});

test.each([
    ['test1', undefined, 1, 10],
    ['test2', undefined, 1, undefined],
    ['test1', 'page', 1, undefined],
    ['test2', 'snippet', 1, undefined],
])('Search results for "%s" in index "%s" should be loaded from server', async(query, index, page, limit) => {
    const result = [
        {id: 1},
    ];

    const searchPromise = Promise.resolve({
        _embedded: {
            result,
        },
    });

    ResourceRequester.getList.mockReturnValue(searchPromise);

    expect(searchStore.loading).toEqual(false);
    searchStore.search(query, index);
    expect(ResourceRequester.getList).toBeCalledWith('search', {q: query, index, page, limit});
    expect(searchStore.loading).toEqual(true);

    await searchPromise; // Wait for the promise to resolve
    return searchPromise.then(() => {
        expect(searchStore.loading).toEqual(false);
        expect(searchStore.result).toEqual(result);
    });
});

test('Do not send search request when no search term is given and reset to empty array', () => {
    searchStore.search(undefined);
    expect(ResourceRequester.getList).not.toBeCalled();
    expect(searchStore.result).toEqual([]);
});
