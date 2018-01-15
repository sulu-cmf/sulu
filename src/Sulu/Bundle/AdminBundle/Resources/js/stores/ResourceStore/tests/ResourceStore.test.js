// @flow
import {observable, toJS, when} from 'mobx';
import ResourceStore from '../ResourceStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn(),
    put: jest.fn(),
    post: jest.fn(),
    delete: jest.fn(),
}));

test('Create data object for schema', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.changeSchema({
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    });

    expect(Object.keys(resourceStore.data)).toHaveLength(2);
    expect(resourceStore.data).toEqual({
        title: null,
        description: null,
    });

    resourceStore.changeSchema({
        text: {
            label: 'Text',
            type: 'text_line',
        },
    });
});

test('Create data object for schema with sections', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.changeSchema({
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    });

    expect(resourceStore.data).toEqual({
        item11: null,
        item21: null,
    });

    resourceStore.changeSchema({
        text: {
            label: 'Text',
            type: 'text_line',
        },
    });
});

test('Change schema should keep data', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };
    resourceStore.changeSchema({
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    });

    expect(Object.keys(resourceStore.data)).toHaveLength(3);
    expect(resourceStore.data).toEqual({
        title: 'Title',
        description: null,
        slogan: 'Slogan',
    });
});

test('Should be marked dirty when value is changed', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());

    const resourceStore = new ResourceStore('snippets', '1');
    expect(resourceStore.dirty).toBe(false);
    resourceStore.set('test', 'value');

    expect(resourceStore.data.test).toBe('value');
    expect(resourceStore.dirty).toBe(true);
});

test('Should load the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable()}, {test: 10});
    resourceStore.setLocale('en');
    expect(ResourceRequester.get).toBeCalledWith('snippets', '3', {locale: 'en', test: 10});
    return promise.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});
    });
});

test('Should load without locale the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '3');
    expect(ResourceRequester.get).toBeCalledWith('snippets', '3', {});
    return promise.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});
    });
});

test('Should not load the data with the ResourceRequester if no resource-id is provided', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    new ResourceStore('snippets', null, {locale: observable()});
    expect(ResourceRequester.get).not.toBeCalled();
});

test('Should not load the data with the ResourceRequester if locale should be provided but is not', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    new ResourceStore('snippets', '3', {locale: observable()});
    expect(ResourceRequester.get).not.toBeCalled();
});

test('Loading flag should be set to true when loading', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.loading = false;
    resourceStore.setLocale('en');

    resourceStore.load();
    expect(resourceStore.loading).toBe(true);
});

test('Loading flag should be set to false when loading has finished', () => {
    const promise = Promise.resolve();
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.setLocale('en');
    resourceStore.loading = true;

    resourceStore.load();
    return promise.then(() => {
        expect(resourceStore.loading).toBe(false);
    });
});

test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.saving = false;
    resourceStore.setLocale('en');

    resourceStore.save();
    expect(resourceStore.saving).toBe(true);
});

test('Saving flag should be set to false when saving has finished', () => {
    const promise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.setLocale('en');
    resourceStore.saving = true;

    resourceStore.save();
    return promise.then(() => {
        expect(resourceStore.saving).toBe(false);
    });
});

test('Save the store should send a PUT request', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('de');
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save({test: 10});
    expect(ResourceRequester.put).toBeCalledWith('snippets', '3', {title: 'Title'}, {locale: 'de', test: 10});
});

test('Save the store should send a PUT request without a locale', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', null, {locale: observable()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('de');
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save({test: 10});
    expect(ResourceRequester.post).toBeCalledWith('snippets', {title: 'Title'}, {locale: 'de', test: 10});
});

test('Save the store without an id should send a POST request', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', null, {locale: observable()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('de');
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save({test: 10});
    expect(ResourceRequester.post).toBeCalledWith('snippets', {title: 'Title'}, {locale: 'de', test: 10});
});

test('Save the store without an id should send a POST request without a locale', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', null, {});
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save({test: 10});
    expect(ResourceRequester.post).toBeCalledWith('snippets', {title: 'Title'}, {test: 10});
});

test('Saving flag should be set to true when deleting', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.saving = false;
    resourceStore.setLocale('en');

    resourceStore.delete();
    expect(resourceStore.saving).toBe(true);
});

test('Saving flag should be set to false when deleting has finished', () => {
    const promise = Promise.resolve();
    ResourceRequester.delete.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.setLocale('en');
    resourceStore.saving = true;

    resourceStore.delete();
    return promise.then(() => {
        expect(resourceStore.saving).toBe(false);
    });
});

test('Calling the delete method should send a DELETE request', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '3', {});
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.delete();
    expect(ResourceRequester.delete).toBeCalledWith('snippets', '3');
});

test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.saving = false;

    resourceStore.save();
    expect(resourceStore.saving).toBe(true);
});

test('Saving and dirty flag should be set and data should be updated to false when saving has finished', () => {
    const data = {changed: 'later'};
    const promise = Promise.resolve(data);
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.saving = true;
    resourceStore.dirty = true;

    resourceStore.save();

    return promise.then(() => {
        expect(resourceStore.saving).toBe(false);
        expect(resourceStore.dirty).toBe(false);
        expect(resourceStore.data).toEqual(data);
    });
});

test('Saving and dirty flag should be set to false when saving has failed', (done) => {
    const promise = Promise.reject(new Error('An error occured!'));
    ResourceRequester.get.mockReturnValue(Promise.resolve({title: 'Title to stay!'}));
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('en');
    resourceStore.saving = true;
    resourceStore.dirty = true;

    resourceStore.save();

    return promise.catch(() => {
        when(
            () => !resourceStore.saving,
            () => {
                expect(resourceStore.saving).toBe(false);
                expect(resourceStore.dirty).toBe(true);
                expect(resourceStore.data).toEqual({title: 'Title to stay!'});
                done();
            }
        );
    });
});

test('Calling setLocale on a non-localizable ResourceStore should throw an exception', () => {
    const resourceStore = new ResourceStore('snippets', '1', {});
    expect(() => resourceStore.setLocale('de')).toThrow();
});

test('Calling the clone method should return a new instance of the ResourceStore with same data', () => {
    const resourceStore = new ResourceStore('snippets', '1', {});
    resourceStore.data = {title: 'Title'};
    const clonedResourceStore = resourceStore.clone();

    expect(toJS(clonedResourceStore.data)).toEqual({title: 'Title'});
    expect(clonedResourceStore.data).not.toBe(resourceStore.data);
    expect(toJS(clonedResourceStore)).not.toBe(resourceStore);
    expect(ResourceRequester.get).toHaveBeenCalledTimes(1);
});
