// @flow
import React from 'react';
import {mount} from 'enzyme';
import {Router} from 'sulu-admin-bundle/services';
import Search from '../Search';
import indexStore from '../stores/indexStore';
import searchStore from '../stores/searchStore';

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/indexStore', () => ({
    loadIndexes: jest.fn(),
}));

jest.mock('../stores/searchStore', () => ({
    indexName: undefined,
    query: undefined,
    results: [],
    search: jest.fn(),
    setPage: jest.fn(),
    setLimit: jest.fn(),
}));

beforeEach(() => {
    searchStore.indexName = undefined;
    searchStore.query = undefined;
    searchStore.loading = false;
    searchStore.result = [];
});

test('Render loader while loading indexes and show SearchField afterwards', () => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const search = mount(<Search router={router} />);

    expect(search.render()).toMatchSnapshot();

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render loader while loading search results', () => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = true;

    const search = mount(<Search router={router} />);

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render hint that nothing was found', () => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [];
    searchStore.query = 'something';

    const search = mount(<Search router={router} />);

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render search results', () => {
    const router = new Router({});

    const indexes = [
        {
            icon: 'su-page',
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
        {
            icon: 'su-contact',
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [
        {
            document: {
                description: 'something',
                id: 3,
                imageUrl: '/image.jgp',
                index: 'page',
                locale: 'de',
                resource: 'page',
                title: 'Test1',
            },
        },
        {
            document: {
                description: 'something 2',
                id: 5,
                imageUrl: undefined,
                index: 'contact',
                locale: undefined,
                resource: 'contact',
                title: 'Max Mustermann',
            },
        },
    ];
    searchStore.query = 'something';

    const search = mount(<Search router={router} />);

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Set the query and index name from the SearchStore as start value', () => {
    const router = new Router({});

    searchStore.indexName = undefined;
    searchStore.query = 'Test';
    searchStore.indexName = 'page';

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const search = mount(<Search router={router} />);

    return indexPromise.then(() => {
        search.update();
        expect(search.find('SearchField input').prop('value')).toEqual('Test');
        expect(search.find('SearchField .indexButton .index').prop('children')).toEqual('Page');
    });
});

test('Search when the search button is clicked', () => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
        {
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const search = mount(<Search router={router} />);

    return indexPromise.then(() => {
        search.update();
        search.find('SearchField input').prop('onChange')({currentTarget: {value: 'Test'}});
        search.find('Icon[name="su-search"]').prop('onClick')();
        expect(searchStore.search).toBeCalledWith('Test', undefined);
    });
});

test('Navigate to route for search result item', () => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {
                    id: 'id',
                    locale: 'locale',
                    'properties/webspace_key': 'webspace',
                },
            },
        },
        {
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {
                    id: 'id',
                },
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [
        {
            document: {
                description: 'something',
                id: 3,
                imageUrl: '/image.jgp',
                index: 'page',
                locale: 'de',
                properties: {
                    webspace_key: 'example',
                },
                resource: 'page',
                title: 'Test1',
            },
        },
        {
            document: {
                description: 'something 2',
                id: 5,
                index: 'contact',
                imageUrl: '/image2.jgp',
                locale: undefined,
                resource: 'contact',
                title: 'Max Mustermann',
            },
        },
    ];
    searchStore.query = 'something';

    const search = mount(<Search router={router} />);

    return indexPromise.then(() => {
        search.update();
        search.find('SearchResult').at(1).find('div').at(0).simulate('click');
        expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.edit_form', {id: 5});
        search.find('SearchResult').at(0).find('div').at(0).simulate('click');
        expect(router.navigate).toHaveBeenLastCalledWith(
            'sulu_page.edit_form',
            {id: 3, locale: 'de', webspace: 'example'}
        );
    });
});
