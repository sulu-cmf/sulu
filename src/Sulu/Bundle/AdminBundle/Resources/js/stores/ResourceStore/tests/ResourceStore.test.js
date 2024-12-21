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

test('Should be marked as initialized after loading the data', () => {
    const promise = Promise.resolve({});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1');

    expect(resourceStore.initialized).toBe(false);

    return promise.then(() => {
        expect(resourceStore.initialized).toBe(true);
        expect(resourceStore.forbidden).toBe(false);
    });
});

test('Should be marked as not dirty after loading the data', () => {
    const promise = Promise.resolve({});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1');

    resourceStore.dirty = true;

    return promise.then(() => {
        expect(resourceStore.dirty).toBe(false);
        expect(resourceStore.forbidden).toBe(false);
    });
});

test('Should be marked as forbidden if loading failed with 403 and reset if next request succeeds', (done) => {
    const promise = Promise.reject({status: 403});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1');

    setTimeout(() => {
        expect(resourceStore.forbidden).toBe(true);
        expect(resourceStore.loading).toBe(false);

        const promise = Promise.resolve({});
        ResourceRequester.get.mockReturnValue(promise);

        resourceStore.load();

        setTimeout(() => {
            expect(resourceStore.forbidden).toBe(false);
            expect(resourceStore.loading).toBe(false);
            done();
        });
    });
});

test('Should be marked as notFound if loading failed with 404 and reset if next request succeeds', (done) => {
    const promise = Promise.reject({status: 404});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1');

    setTimeout(() => {
        expect(resourceStore.notFound).toBe(true);
        expect(resourceStore.loading).toBe(false);

        const promise = Promise.resolve({});
        ResourceRequester.get.mockReturnValue(promise);

        resourceStore.load();

        setTimeout(() => {
            expect(resourceStore.notFound).toBe(false);
            expect(resourceStore.loading).toBe(false);
            done();
        });
    });
});

test('Should be marked as unexpectedError if loading failed with 500 and reset if next request succeeds', (done) => {
    const promise = Promise.reject({status: 500});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1');

    setTimeout(() => {
        expect(resourceStore.unexpectedError).toBe(true);
        expect(resourceStore.loading).toBe(false);

        const promise = Promise.resolve({});
        ResourceRequester.get.mockReturnValue(promise);

        resourceStore.load();

        setTimeout(() => {
            expect(resourceStore.unexpectedError).toBe(false);
            expect(resourceStore.loading).toBe(false);
            done();
        });
    });
});

test('Should be marked as initialized immediately if a new resource is created', () => {
    const resourceStore = new ResourceStore('snippets');
    expect(resourceStore.initialized).toBe(true);
});

test('Should not be marked dirty when value is set', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({}));

    const resourceStore = new ResourceStore('snippets', '1');
    expect(resourceStore.dirty).toBe(false);
    resourceStore.set('test', 'value');

    expect(resourceStore.data.test).toBe('value');
    expect(resourceStore.dirty).toBe(false);
});

test('Should be marked dirty when value is changed', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({}));

    const resourceStore = new ResourceStore('snippets', '1');
    expect(resourceStore.dirty).toBe(false);
    resourceStore.change('test', 'value');

    expect(resourceStore.data.test).toBe('value');
    expect(resourceStore.dirty).toBe(true);
});

test('Should set nested values', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({}));

    const resourceStore = new ResourceStore('snippets', '1');
    expect(resourceStore.dirty).toBe(false);
    resourceStore.change('test1/test2', 'value');

    expect(resourceStore.data.test1.test2).toBe('value');
    expect(resourceStore.dirty).toBe(true);
});

test('Should set values when using path with leading slash', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({}));

    const resourceStore = new ResourceStore('snippets', '1');
    expect(resourceStore.dirty).toBe(false);
    resourceStore.change('/test1/test2', 'value');

    expect(resourceStore.data.test1.test2).toBe('value');
    expect(resourceStore.dirty).toBe(true);
});

test('Should load the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable.box('en')}, {test: 10});
    expect(ResourceRequester.get).toBeCalledWith('snippets', {id: '3', locale: 'en', test: 10});
    return promise.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});
    });
});

test('Should load without locale the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '3');
    const oldData = resourceStore.data;
    expect(ResourceRequester.get).toBeCalledWith('snippets', {id: '3'});
    return promise.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Should load with the idQueryParameter and reset after successful load', () => {
    const promise = Promise.resolve({id: 5, value: 'test'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('users', 2, {}, {}, 'contactId');
    const oldData = resourceStore.data;
    expect(resourceStore.idQueryParameter).toEqual('contactId');
    expect(ResourceRequester.get).toBeCalledWith('users', {contactId: 2});

    return promise.then(() => {
        expect(resourceStore.data).toEqual({id: 5, value: 'test'});
        expect(resourceStore.idQueryParameter).toEqual(undefined);
        expect(resourceStore.id).toEqual(5);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Should not load the data with the ResourceRequester if no resource-id is provided', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    new ResourceStore('snippets', null, {locale: observable.box()});
    expect(ResourceRequester.get).not.toBeCalled();
});

test('Should not load the data with the ResourceRequester if locale should be provided but is not', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    new ResourceStore('snippets', '3', {locale: observable.box()});
    expect(ResourceRequester.get).not.toBeCalled();
});

test('Should load the data with the ResourceRequester if a reload is requested', () => {
    const promise1 = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise1);
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable.box('en')}, {test: 10});
    expect(ResourceRequester.get).toBeCalledWith('snippets', {id: '3', locale: 'en', test: 10});
    return promise1.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});

        const promise2 = Promise.resolve({value: 'new Value'});
        ResourceRequester.get.mockReturnValue(promise2);
        resourceStore.reload();

        expect(ResourceRequester.get).toBeCalledWith('snippets', {id: '3', locale: 'en', test: 10});
        expect(ResourceRequester.get).toHaveBeenCalledTimes(2);

        return promise2.then(() => {
            expect(resourceStore.data).toEqual({value: 'new Value'});
        });
    });
});

test('Loading flag should be set to true when loading', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.loading = false;

    resourceStore.load();
    expect(resourceStore.loading).toBe(true);
});

test('Loading flag should be set to false when loading has finished', () => {
    const promise = Promise.resolve({});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const oldData = resourceStore.data;
    resourceStore.loading = true;

    resourceStore.load();
    return promise.then(() => {
        expect(resourceStore.loading).toBe(false);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.saving = false;

    resourceStore.save();
    expect(resourceStore.saving).toBe(true);
});

test('Saving flag should be set to false when saving has finished', () => {
    const promise = Promise.resolve({});
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.saving = true;

    resourceStore.save();
    return promise.then(() => {
        expect(resourceStore.saving).toBe(false);
    });
});

test('Save the store should send a PUT request', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable.box()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('de');
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save({test: 10});
    expect(ResourceRequester.put).toBeCalledWith('snippets', {title: 'Title'}, {id: '3', locale: 'de', test: 10});
});

test('Save the store should send a PUT request without a locale', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', null, {locale: observable.box()});

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
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', null, {locale: observable.box()});

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
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', null, {});
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save({test: 10});
    expect(ResourceRequester.post).toBeCalledWith('snippets', {title: 'Title'}, {test: 10});
});

test('Saving and dirty flag should be set to false when creating has failed', (done) => {
    const error = new Error('An error occured!');
    const promise = Promise.reject(error);
    ResourceRequester.post.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', undefined, {locale: observable.box()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('en');
    resourceStore.saving = true;
    resourceStore.dirty = true;

    const savePromise = resourceStore.save();

    return savePromise.catch((promiseError) => {
        expect(promiseError).toBe(error);
        when(
            () => !resourceStore.saving,
            (): void => {
                expect(resourceStore.saving).toBe(false);
                expect(resourceStore.dirty).toBe(true);
                expect(resourceStore.data).toEqual({});
                done();
            }
        );
    });
});

test('Deleting should throw error when id is undefined', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.data = {id: undefined};

    expect(() => resourceStore.delete()).toThrow('Cannot delete resource with an undefined "id"');
});

test('Deleting with delete_locale should throw error when locale is undefined', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {});
    resourceStore.data = {id: 1};

    expect(() => {
        resourceStore.delete({deleteLocale: true});
    }).toThrow('Cannot delete a localized resource with an undefined "locale"');
});

test('Deleting without delete_locale should not throw error when locale is undefined', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box()});
    resourceStore.data = {id: 1};

    expect(() => {
        resourceStore.delete({delete_locale: false});
    }).not.toThrow();
});

test('Deleting flag should be set to true when deleting', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.data = {id: 1};
    resourceStore.deleting = false;

    resourceStore.delete();
    expect(resourceStore.saving).toBe(false);
    expect(resourceStore.deleting).toBe(true);
});

test('Deleting flag and id should be reset to false when deleting has finished', () => {
    const promise = Promise.resolve({});
    ResourceRequester.delete.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.data = {id: 1};
    const oldData = resourceStore.data;
    resourceStore.deleting = false;

    resourceStore.delete();
    expect(resourceStore.deleting).toBe(true);

    return promise.then(() => {
        expect(resourceStore.deleting).toBe(false);
        expect(resourceStore.id).toBe(undefined);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Calling the delete method should send a DELETE request', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '3', {});
    resourceStore.data = {id: 3, title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.delete();
    expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 3});
});

test('Calling the delete method with options should send a DELETE request', () => {
    ResourceRequester.delete.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '3', {});
    resourceStore.data = {id: 3, title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.delete({test: 'value'});
    expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 3, test: 'value'});
});

test('Moving flag should be set to true when moving', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.data = {id: 1};
    resourceStore.moving = false;

    resourceStore.move(1);
    expect(resourceStore.saving).toBe(false);
    expect(resourceStore.moving).toBe(true);
});

test('Moving flag and id should be reset to false when moving has finished', () => {
    const promise = Promise.resolve({});
    ResourceRequester.post.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', 1, {locale: observable.box('en')});
    resourceStore.data = {id: 1};
    const oldData = resourceStore.data;
    resourceStore.moving = false;

    resourceStore.move(5);
    expect(resourceStore.moving).toBe(true);

    return promise.then(() => {
        expect(resourceStore.moving).toBe(false);
        expect(ResourceRequester.get).toBeCalledWith('snippets', {id: 1, locale: 'en'});
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Calling the move method should send a POST request', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', 3, {});
    resourceStore.data = {id: 3, title: 'Title'};

    resourceStore.move(9);
    expect(ResourceRequester.post).toBeCalledWith(
        'snippets',
        undefined,
        {action: 'move', destination: 9, id: 3, locale: undefined}
    );
});

test('Calling the move method should send a POST request with locale', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', 3, {locale: observable.box('de')});
    resourceStore.data = {id: 3, title: 'Title'};

    resourceStore.move(9);
    expect(ResourceRequester.post).toBeCalledWith(
        'snippets',
        undefined,
        {action: 'move', destination: 9, id: 3, locale: 'de'}
    );
});

test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box()});
    resourceStore.saving = false;

    resourceStore.save();
    expect(resourceStore.saving).toBe(true);
});

test('Response should be returned when updating', () => {
    const data = {};
    const promise = Promise.resolve(data);
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box()});
    const oldData = resourceStore.data;
    resourceStore.saving = false;

    return resourceStore.save().then((responseData) => {
        expect(responseData).toBe(data);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Response should be returned when creating', () => {
    const data = {};
    const promise = Promise.resolve(data);
    ResourceRequester.post.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', undefined, {locale: observable.box()});
    const oldData = resourceStore.data;
    resourceStore.saving = false;

    return resourceStore.save().then((responseData) => {
        expect(responseData).toBe(data);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Saving and dirty flag should be set and data should be updated to false when saving has finished', () => {
    const data = {changed: 'later'};
    const promise = Promise.resolve(data);
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box()});
    const oldData = resourceStore.data;
    resourceStore.saving = true;
    resourceStore.dirty = true;

    resourceStore.save();

    return promise.then(() => {
        expect(resourceStore.saving).toBe(false);
        expect(resourceStore.dirty).toBe(false);
        expect(resourceStore.data).toEqual(data);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Saving and dirty flag should be set to false when updating has failed', (done) => {
    const error = new Error('An error occured!');
    const promise = Promise.reject(error);
    const loadingPromise = Promise.resolve({title: 'Title to stay!'});
    ResourceRequester.get.mockReturnValue(loadingPromise);
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box()});

    if (!resourceStore.locale) {
        throw new Error('The resourceStore should have a locale');
    }

    resourceStore.locale.set('en');

    return loadingPromise.then(() => {
        resourceStore.saving = true;
        resourceStore.dirty = true;
        const savePromise = resourceStore.save();

        return savePromise.catch((promiseError) => {
            expect(promiseError).toBe(error);
            when(
                () => !resourceStore.saving,
                (): void => {
                    expect(resourceStore.saving).toBe(false);
                    expect(resourceStore.dirty).toBe(true);
                    expect(resourceStore.data).toEqual({title: 'Title to stay!'});
                    done();
                }
            );
        });
    });
});

test('Saving should consider the passed idQueryParameter flag and reset it after the correct id was passed', () => {
    const promise = Promise.resolve({id: 3});
    ResourceRequester.get.mockReturnValue(Promise.resolve({}));
    ResourceRequester.post.mockReturnValue(promise);
    const resourceStore = new ResourceStore('users', 2, {}, {}, 'contactId');
    const oldData = resourceStore.data;
    expect(resourceStore.idQueryParameter).toEqual('contactId');
    expect(ResourceRequester.get).toBeCalledWith('users', {contactId: 2});

    resourceStore.save();

    return promise.then(() => {
        expect(resourceStore.data).toEqual({id: 3});
        expect(resourceStore.idQueryParameter).toEqual(undefined);
        expect(resourceStore.id).toEqual(3);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Copy the content from different locale', () => {
    const resourceStore = new ResourceStore('pages', 4, {locale: observable.box('en')}, {webspace: 'sulu_io'});
    resourceStore.set('content', 'old content');
    expect(resourceStore.data).toEqual({content: 'old content'});
    const oldData = resourceStore.data;

    const germanContent = {id: 3, content: 'new content'};
    const promise = Promise.resolve(germanContent);
    ResourceRequester.post.mockReturnValue(promise);

    resourceStore.copyFromLocale('de');

    expect(ResourceRequester.post)
        .toBeCalledWith('pages', {}, {action: 'copy-locale', id: 4, locale: 'en', dest: 'en', src: 'de'});

    return promise.then(() => {
        expect(resourceStore.data).toEqual(germanContent);
        expect(resourceStore.data).toBe(oldData);
    });
});

test('Copying the content from different locale should fail if no id is given', () => {
    const resourceStore = new ResourceStore('pages');
    expect(() => resourceStore.copyFromLocale('de')).toThrow(/for new objects/);
});

test('Copying the content from different locale should fail if no locale is given', () => {
    const resourceStore = new ResourceStore('pages', 4);
    expect(() => resourceStore.copyFromLocale('de')).toThrow(/with locales/);
});

test('Cloning should return a new instance of the ResourceStore with same data', () => {
    const resourceStore = new ResourceStore('snippets', '1', {});
    resourceStore.data = {title: 'Title'};
    resourceStore.loading = false;
    const clonedResourceStore = resourceStore.clone();

    expect(toJS(clonedResourceStore.data)).toEqual({title: 'Title'});
    expect(clonedResourceStore.data).not.toBe(resourceStore.data);
    expect(toJS(clonedResourceStore)).not.toBe(resourceStore);
    expect(ResourceRequester.get).toHaveBeenCalledTimes(1);
});

test('Cloning should return a new instance of the ResourceStore which updates after changing locale', () => {
    const locale = observable.box('en');
    const resourceStore = new ResourceStore('snippets', '1', {locale});
    resourceStore.data = {title: 'Title'};
    resourceStore.loading = false;
    const clonedResourceStore = resourceStore.clone();

    expect(toJS(clonedResourceStore.data)).toEqual({title: 'Title'});
    expect(clonedResourceStore.data).not.toBe(resourceStore.data);
    expect(toJS(clonedResourceStore)).not.toBe(resourceStore);
    expect(ResourceRequester.get).toHaveBeenCalledTimes(1);

    resourceStore.destroy();

    locale.set('de');
    expect(ResourceRequester.get).toHaveBeenCalledTimes(2);
});

test('Cloning during loading should return a new instance of the ResourceStore with same data', () => {
    const snippet = {
        title: 'Snippet',
    };
    let snippetResolve;
    const snippetPromise = new Promise((resolve) => snippetResolve = resolve);
    ResourceRequester.get.mockReturnValue(snippetPromise);
    const resourceStore = new ResourceStore('snippets', '1', {});
    const clonedResourceStore = resourceStore.clone();

    expect(resourceStore.loading).toEqual(true);
    expect(clonedResourceStore.loading).toEqual(true);

    if (!snippetResolve) {
        throw new Error('The resolve function for snippets must be set!');
    }
    snippetResolve(snippet);

    return snippetPromise.then(() => {
        expect(toJS(resourceStore.data)).toEqual({title: 'Snippet'});
        expect(toJS(clonedResourceStore.data)).toEqual({title: 'Snippet'});
        expect(clonedResourceStore.data).not.toBe(resourceStore.data);
        expect(toJS(clonedResourceStore)).not.toBe(resourceStore);
        expect(ResourceRequester.get).toHaveBeenCalledTimes(1);
    });
});

test('Should set the internal id if id is set using set', () => {
    const resourceStore = new ResourceStore('media');
    expect(resourceStore.id).toBe(undefined);
    resourceStore.set('id', 4);
    expect(resourceStore.id).toBe(4);
});

test('Should set the internal id if id is set with leading slash', () => {
    const resourceStore = new ResourceStore('media');
    expect(resourceStore.id).toBe(undefined);
    resourceStore.set('/id', 4);
    expect(resourceStore.id).toBe(4);
});

test('Should set the internal id if id is set using setMultiple', () => {
    const resourceStore = new ResourceStore('media');
    expect(resourceStore.id).toBe(undefined);
    resourceStore.setMultiple({
        id: 7,
    });
    expect(resourceStore.id).toBe(7);
});
