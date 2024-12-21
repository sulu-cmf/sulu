//@flow
import resourceViewRegistry from '../../registries/resourceViewRegistry';

beforeEach(() => {
    resourceViewRegistry.clear();
});

test('Clear routes from ResourceViewRegistry should empty list', () => {
    resourceViewRegistry.addResourceViews({
        resource: {
            views: {
                list: 'sulu_page.pages_list',
                detail: 'sulu_page.page_edit_form',
            },
        },
    });

    expect(Object.keys(resourceViewRegistry.resourceViews)).toHaveLength(1);

    resourceViewRegistry.clear();
    expect(Object.keys(resourceViewRegistry.resourceViews)).toHaveLength(0);
});

test('Get view from RouteRegistry', () => {
    resourceViewRegistry.addResourceViews({
        pages: {
            views: {
                list: 'sulu_page.pages_list',
                detail: 'sulu_page.page_edit_form',
            },
        },
    });

    const listView = resourceViewRegistry.get('list', 'pages');
    const detailView = resourceViewRegistry.get('detail', 'pages');

    expect(listView).toBe('sulu_page.pages_list');
    expect(detailView).toBe('sulu_page.page_edit_form');
});

test('Get a non-existing resource should throw an exception', () => {
    expect(() => resourceViewRegistry.get('detail', 'not-exist'))
        .toThrow('The resource "not-exist" was not found.');
});

test('Get a non-existing view should throw an exception', () => {
    resourceViewRegistry.addResourceViews({
        pages: {
            views: {
                list: 'sulu_page.pages_list',
                detail: 'sulu_page.page_edit_form',
            },
        },
    });

    expect(() => resourceViewRegistry.get('non-exist', 'pages'))
        .toThrow('The resource view "non-exist" for resource "pages" was not found.');
});

test('Has view from RouteRegistry should return true if exists', () => {
    resourceViewRegistry.addResourceViews({
        pages: {
            views: {
                detail: 'sulu_page.page_edit_form',
            },
        },
    });

    expect(resourceViewRegistry.has('detail', 'pages')).toBe(true);
});

test('Has view from RouteRegistry should return false if not exists', () => {
    expect(resourceViewRegistry.has('detail', 'pages')).toBe(false);
});
