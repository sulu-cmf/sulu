// @flow
import {observable, toJS, when} from 'mobx';
import FormStore from '../../stores/FormStore';
import ResourceStore from '../../../../stores/ResourceStore';
import metadataStore from '../../stores/MetadataStore';

jest.mock('../../../../stores/ResourceStore', () => function(resourceKey, id, options) {
    this.id = id;
    this.resourceKey = resourceKey;
    this.save = jest.fn().mockReturnValue(Promise.resolve());
    this.delete = jest.fn().mockReturnValue(Promise.resolve());
    this.set = jest.fn();
    this.setMultiple = jest.fn();
    this.change = jest.fn();
    this.copyFromLocale = jest.fn();
    this.data = {};
    this.loading = false;

    if (options) {
        this.locale = options.locale;
    }
});

jest.mock('../../stores/MetadataStore', () => ({}));

beforeEach(() => {
    // $FlowFixMe
    metadataStore.getSchema = jest.fn().mockReturnValue(Promise.resolve({}));
    // $FlowFixMe
    metadataStore.getJsonSchema = jest.fn().mockReturnValue(Promise.resolve({}));
    // $FlowFixMe
    metadataStore.getSchemaTypes = jest.fn().mockReturnValue(Promise.resolve({}));
});

test('Create data object for schema', () => {
    const metadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1');
    const formStore = new FormStore(resourceStore);
    expect(formStore.schemaLoading).toEqual(true);

    setTimeout(() => {
        expect(formStore.schemaLoading).toEqual(false);
        expect(Object.keys(formStore.data)).toHaveLength(2);
        expect(resourceStore.set).not.toBeCalledWith('template', expect.anything());
        expect(formStore.data).toEqual({
            title: undefined,
            description: undefined,
        });
        formStore.destroy();
    }, 0);
});

test('Evaluate all disabledConditions and visibleConditions for schema', (done) => {
    const metadata = {
        item1: {
            type: 'text_line',
        },
        item2: {
            type: 'text_line',
            disabledCondition: 'item1 != "item2"',
            visibleCondition: 'item1 == "item2"',
        },
        section: {
            items: {
                item31: {
                    type: 'text_line',
                },
                item32: {
                    type: 'text_line',
                    disabledCondition: 'item1 != "item32"',
                    visibleCondition: 'item1 == "item32"',
                },
            },
            type: 'section',
            disabledCondition: 'item1 != "section"',
            visibleCondition: 'item1 == "section"',
        },
        block: {
            types: {
                text_line: {
                    form: {
                        item41: {
                            type: 'text_line',
                            disabledCondition: 'item1 != "item41"',
                            visibleCondition: 'item1 == "item41"',
                        },
                        item42: {
                            type: 'text_line',
                        },
                    },
                },
            },
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1');
    const formStore = new FormStore(resourceStore);

    setTimeout(() => {
        const sectionItems = formStore.schema.section.items;
        if (!sectionItems) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes = formStore.schema.block.types;
        if (!blockTypes) {
            throw new Error('Block types should be defined!');
        }

        expect(formStore.schema.item2.disabled).toEqual(true);
        expect(formStore.schema.item2.visible).toEqual(false);
        expect(sectionItems.item32.disabled).toEqual(true);
        expect(sectionItems.item32.visible).toEqual(false);
        expect(formStore.schema.section.disabled).toEqual(true);
        expect(formStore.schema.section.visible).toEqual(false);
        expect(blockTypes.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes.text_line.form.item41.visible).toEqual(false);

        resourceStore.data = {item1: 'item2'};
        expect(formStore.schema.item2.disabled).toEqual(true);
        expect(formStore.schema.item2.visible).toEqual(false);

        formStore.finishField('/item1').then(() => {
            const sectionItems = formStore.schema.section.items;
            if (!sectionItems) {
                throw new Error('Section items should be defined!');
            }
            const blockTypes = formStore.schema.block.types;
            if (!blockTypes) {
                throw new Error('Block types should be defined!');
            }

            expect(formStore.schema.item2.disabled).toEqual(false);
            expect(formStore.schema.item2.visible).toEqual(true);
            expect(sectionItems.item32.disabled).toEqual(true);
            expect(sectionItems.item32.visible).toEqual(false);
            expect(formStore.schema.section.disabled).toEqual(true);
            expect(formStore.schema.section.visible).toEqual(false);
            expect(blockTypes.text_line.form.item41.disabled).toEqual(true);
            expect(blockTypes.text_line.form.item41.visible).toEqual(false);

            resourceStore.data = {item1: 'item32'};
            formStore.finishField('/item1').then(() => {
                const sectionItems = formStore.schema.section.items;
                if (!sectionItems) {
                    throw new Error('Section items should be defined!');
                }
                const blockTypes = formStore.schema.block.types;
                if (!blockTypes) {
                    throw new Error('Block types should be defined!');
                }

                expect(formStore.schema.item2.disabled).toEqual(true);
                expect(formStore.schema.item2.visible).toEqual(false);
                expect(sectionItems.item32.disabled).toEqual(false);
                expect(sectionItems.item32.visible).toEqual(true);
                expect(formStore.schema.section.disabled).toEqual(true);
                expect(formStore.schema.section.visible).toEqual(false);
                expect(blockTypes.text_line.form.item41.disabled).toEqual(true);
                expect(blockTypes.text_line.form.item41.visible).toEqual(false);

                resourceStore.data = {item1: 'section'};
                formStore.finishField('/item1').then(() => {
                    const sectionItems = formStore.schema.section.items;
                    if (!sectionItems) {
                        throw new Error('Section items should be defined!');
                    }
                    const blockTypes = formStore.schema.block.types;
                    if (!blockTypes) {
                        throw new Error('Block types should be defined!');
                    }

                    expect(formStore.schema.item2.disabled).toEqual(true);
                    expect(formStore.schema.item2.visible).toEqual(false);
                    expect(sectionItems.item32.disabled).toEqual(true);
                    expect(sectionItems.item32.visible).toEqual(false);
                    expect(formStore.schema.section.disabled).toEqual(false);
                    expect(formStore.schema.section.visible).toEqual(true);
                    expect(blockTypes.text_line.form.item41.disabled).toEqual(true);
                    expect(blockTypes.text_line.form.item41.visible).toEqual(false);

                    resourceStore.data = {item1: 'item41'};
                    formStore.finishField('/item1').then(() => {
                        const sectionItems = formStore.schema.section.items;
                        if (!sectionItems) {
                            throw new Error('Section items should be defined!');
                        }
                        const blockTypes = formStore.schema.block.types;
                        if (!blockTypes) {
                            throw new Error('Block types should be defined!');
                        }

                        expect(formStore.schema.item2.disabled).toEqual(true);
                        expect(formStore.schema.item2.visible).toEqual(false);
                        expect(sectionItems.item32.disabled).toEqual(true);
                        expect(sectionItems.item32.visible).toEqual(false);
                        expect(formStore.schema.section.disabled).toEqual(true);
                        expect(formStore.schema.section.visible).toEqual(false);
                        expect(blockTypes.text_line.form.item41.disabled).toEqual(false);
                        expect(blockTypes.text_line.form.item41.visible).toEqual(true);

                        formStore.destroy();
                        done();
                    });
                });
            });
        });
    }, 0);
});

test('Evaluate disabledConditions and visibleConditions for schema with locale', (done) => {
    const metadata = {
        item: {
            type: 'text_line',
            disabledCondition: '__locale == "en"',
            visibleCondition: '__locale == "de"',
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const formStore = new FormStore(resourceStore);

    setTimeout(() => {
        expect(formStore.schema.item.disabled).toEqual(true);
        expect(formStore.schema.item.visible).toEqual(false);
        done();
    }, 0);
});

test('Evaluate disabledConditions and visibleConditions when changing locale', (done) => {
    const metadata = {
        item: {
            type: 'text_line',
            disabledCondition: '__locale == "en"',
            visibleCondition: '__locale == "de"',
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const locale = observable.box('en');
    const resourceStore = new ResourceStore('snippets', '1', {locale});
    const formStore = new FormStore(resourceStore);

    setTimeout(() => {
        expect(formStore.schema.item.disabled).toEqual(true);
        expect(formStore.schema.item.visible).toEqual(false);

        locale.set('de');
        setTimeout(() => {
            expect(formStore.schema.item.disabled).toEqual(false);
            expect(formStore.schema.item.visible).toEqual(true);
            done();
        }, 0);
    }, 0);
});

test('Read resourceKey from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets');
    const formStore = new FormStore(resourceStore);

    expect(formStore.resourceKey).toEqual('snippets');
});

test('Read locale from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const formStore = new FormStore(resourceStore);

    expect(formStore.locale && formStore.locale.get()).toEqual('en');
});

test('Read id from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const formStore = new FormStore(resourceStore);

    expect(formStore.id).toEqual('1');
});

test('Read saving flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.saving = true;
    const formStore = new FormStore(resourceStore);

    expect(formStore.saving).toEqual(true);
});

test('Read deleting flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.deleting = true;
    const formStore = new FormStore(resourceStore);

    expect(formStore.deleting).toEqual(true);
});

test('Read dirty flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.dirty = true;
    const formStore = new FormStore(resourceStore);

    expect(formStore.dirty).toEqual(true);
});

test('Set dirty flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const formStore = new FormStore(resourceStore);
    formStore.dirty = true;

    expect(formStore.dirty).toEqual(true);
});

test('Set template property of ResourceStore to first type be default', () => {
    const metadata = {};

    const schemaTypesPromise = Promise.resolve({
        type1: {},
        type2: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets');
    const formStore = new FormStore(resourceStore);

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(resourceStore.set).toBeCalledWith('template', 'type1');
        formStore.destroy();
    });
});

test('Set template property of ResourceStore from the loaded data', () => {
    const metadata = {};

    const schemaTypesPromise = Promise.resolve({
        type1: {},
        type2: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {template: 'type2'};
    const formStore = new FormStore(resourceStore);

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(resourceStore.set).toBeCalledWith('template', 'type2');
        formStore.destroy();
    });
});

test('Create data object for schema with sections', () => {
    const metadata = {
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
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const formStore = new FormStore(new ResourceStore('snippets', '1'));

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(formStore.data).toEqual({
            item11: undefined,
            item21: undefined,
        });
        formStore.destroy();
    });
});

test('Change schema should keep data', () => {
    const metadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const formStore = new FormStore(resourceStore);

    setTimeout(() => {
        expect(Object.keys(formStore.data)).toHaveLength(3);
        expect(formStore.data).toEqual({
            title: 'Title',
            description: undefined,
            slogan: 'Slogan',
        });
        formStore.destroy();
    }, 0);
});

test('Change type should update schema and data', (done) => {
    const schemaTypesPromise = Promise.resolve({});
    const sidebarMetadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };
    const sidebarPromise = Promise.resolve(sidebarMetadata);
    const jsonSchemaPromise = Promise.resolve({});

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };

    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    metadataStore.getSchema.mockReturnValue(sidebarPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);
    const formStore = new FormStore(resourceStore);
    const cachedPathsByTag = formStore.pathsByTag;

    setTimeout(() => {
        expect(formStore.rawSchema).toBe(sidebarMetadata);
        expect(formStore.pathsByTag).not.toBe(cachedPathsByTag);
        expect(formStore.data).toEqual({
            title: 'Title',
            description: undefined,
            slogan: 'Slogan',
        });
        formStore.destroy();
        done();
    }, 0);
});

test('Change type should throw an error if no types are available', () => {
    const promise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(promise);

    const resourceStore = new ResourceStore('snippets', '1');
    const formStore = new FormStore(resourceStore);

    return promise.then(() => {
        expect(() => formStore.changeType('test')).toThrow(/cannot handle types/);
    });
});

test('types property should be returning types from server', () => {
    const types = {
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    };
    const promise = Promise.resolve(types);
    metadataStore.getSchemaTypes.mockReturnValue(promise);

    const formStore = new FormStore(new ResourceStore('snippets', '1'));
    expect(toJS(formStore.types)).toEqual({});
    expect(formStore.typesLoading).toEqual(true);

    return promise.then(() => {
        expect(toJS(formStore.types)).toEqual(types);
        expect(formStore.typesLoading).toEqual(false);
        formStore.destroy();
    });
});

test('Type should be set from response', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        template: 'sidebar',
    };

    const schemaTypesPromise = Promise.resolve({
        sidebar: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const formStore = new FormStore(resourceStore);

    return schemaTypesPromise.then(() => {
        expect(formStore.type).toEqual('sidebar');
    });
});

test('Type should not be set from response if types are not supported', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        template: 'sidebar',
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const formStore = new FormStore(resourceStore);

    return schemaTypesPromise.then(() => {
        expect(formStore.type).toEqual(undefined);
    });
});

test('Changing type should set the appropriate property in the ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        template: 'sidebar',
    };

    const schemaTypesPromise = Promise.resolve({
        sidebar: {},
        footer: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const formStore = new FormStore(resourceStore);

    return metadataPromise.then(() => {
        formStore.changeType('footer');
        expect(formStore.type).toEqual('footer');
        setTimeout(() => { // The observe command is executed later
            expect(resourceStore.set).toBeCalledWith('template', 'footer');
        });
    });
});

test('Changing type should throw an exception if types are not supported', () => {
    const resourceStore = new ResourceStore('snippets', '1');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const formStore = new FormStore(resourceStore);

    return schemaTypesPromise.then(() => {
        expect(() => formStore.changeType('sidebar'))
            .toThrow(/"snippets" handled by this FormStore cannot handle types/);
    });
});

test('Loading flag should be set to true as long as schema is loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable.box()}));
    formStore.resourceStore.loading = false;

    expect(formStore.loading).toBe(true);
    formStore.destroy();
});

test('Loading flag should be set to true as long as data is loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable.box()}));
    formStore.resourceStore.loading = true;
    formStore.schemaLoading = false;

    expect(formStore.loading).toBe(true);
    formStore.destroy();
});

test('Loading flag should be set to false after data and schema have been loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable.box()}));
    formStore.resourceStore.loading = false;
    formStore.schemaLoading = false;

    expect(formStore.loading).toBe(false);
    formStore.destroy();
});

test('Save the store should call the resourceStore save function', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3', {locale: observable.box()}));

    formStore.save();
    expect(formStore.resourceStore.save).toBeCalledWith({});
    formStore.destroy();
});

test('Save the store should call the resourceStore save function with the passed options', () => {
    const formStore = new FormStore(
        new ResourceStore('snippets', '3', {locale: observable.box()}),
        {option1: 'value1', option2: 'value2'}
    );

    formStore.save({option: 'value'});
    expect(formStore.resourceStore.save).toBeCalledWith({option: 'value', option1: 'value1', option2: 'value2'});
    formStore.destroy();
});

test('Save the store should reject if request has failed', (done) => {
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const resourceStore = new ResourceStore('snippets', '3');
    const error = {
        text: 'Something failed',
    };
    const errorResponse = {
        json: jest.fn().mockReturnValue(Promise.resolve(error)),
    };
    resourceStore.save.mockReturnValue(Promise.reject(errorResponse));
    const formStore = new FormStore(resourceStore);

    resourceStore.data = {
        blocks: [
            {
                text: 'Test',
            },
            {
                text: 'T',
            },
        ],
    };

    when(
        () => !formStore.schemaLoading,
        (): void => {
            const savePromise = formStore.save();
            savePromise.catch(() => {
                expect(toJS(formStore.errors)).toEqual({});
            });

            // $FlowFixMe
            expect(savePromise).rejects.toEqual(error).then(() => done());
        }
    );
});

test('Save the store should validate the current data', (done) => {
    const jsonSchemaPromise = Promise.resolve({
        required: ['title', 'blocks'],
        properties: {
            blocks: {
                type: 'array',
                items: {
                    type: 'object',
                    oneOf: [
                        {
                            properties: {
                                text: {
                                    type: 'string',
                                    minLength: 3,
                                },
                            },
                        },
                    ],
                },
            },
        },
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const resourceStore = new ResourceStore('snippets', '3');
    const formStore = new FormStore(resourceStore);

    resourceStore.data = {
        blocks: [
            {
                text: 'Test',
            },
            {
                text: 'T',
            },
        ],
    };

    when(
        () => !formStore.schemaLoading,
        (): void => {
            const savePromise = formStore.save();
            savePromise.catch(() => {
                expect(toJS(formStore.errors)).toEqual({
                    title: {
                        keyword: 'required',
                        parameters: {
                            missingProperty: 'title',
                        },
                    },
                    blocks: [
                        undefined,
                        {
                            text: {
                                keyword: 'minLength',
                                parameters: {
                                    limit: 3,
                                },
                            },
                        },
                    ],
                });
            });

            // $FlowFixMe
            expect(savePromise).rejects.toEqual(expect.any(String)).then(() => done());
        }
    );
});

test('Delete should delegate the call to resourceStore', () => {
    const deletePromise = Promise.resolve();
    const resourceStore = new ResourceStore('snippets', 3);
    resourceStore.delete.mockReturnValue(deletePromise);

    const formStore = new FormStore(resourceStore);
    const returnedDeletePromise = formStore.delete();

    expect(resourceStore.delete).toBeCalledWith({});
    expect(returnedDeletePromise).toBe(deletePromise);
});

test('Delete should delegate the call to resourceStore with options', () => {
    const deletePromise = Promise.resolve();
    const resourceStore = new ResourceStore('snippets', 3);
    resourceStore.delete.mockReturnValue(deletePromise);

    const formStore = new FormStore(resourceStore, {webspace: 'sulu_io'});
    const returnedDeletePromise = formStore.delete();

    expect(resourceStore.delete).toBeCalledWith({webspace: 'sulu_io'});
    expect(returnedDeletePromise).toBe(deletePromise);
});

test('Data attribute should return the data from the resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    formStore.resourceStore.data = {
        title: 'Title',
    };

    expect(formStore.data).toBe(formStore.resourceStore.data);
    formStore.destroy();
});

test('Set should be passed to resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    formStore.set('title', 'Title');

    expect(formStore.resourceStore.set).toBeCalledWith('title', 'Title');
    formStore.destroy();
});

test('SetMultiple should be passed to resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    const data = {
        title: 'Title',
        description: 'Description',
    };
    formStore.setMultiple(data);

    expect(formStore.resourceStore.setMultiple).toBeCalledWith(data);
    formStore.destroy();
});

test('Destroying the store should call all the disposers', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '2'));
    formStore.schemaDisposer = jest.fn();
    formStore.typeDisposer = jest.fn();
    formStore.updateFieldPathEvaluationsDisposer = jest.fn();

    formStore.destroy();

    expect(formStore.schemaDisposer).toBeCalled();
    expect(formStore.typeDisposer).toBeCalled();
    expect(formStore.updateFieldPathEvaluationsDisposer).toBeCalled();
});

test('Destroying the store should not fail if no disposers are available', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '2'));
    formStore.schemaDisposer = undefined;
    formStore.typeDisposer = undefined;

    formStore.destroy();
});

test('Should return value for property path', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = {test: 'value'};

    const formStore = new FormStore(resourceStore);

    expect(formStore.getValueByPath('/test')).toEqual('value');
});

test('Return all the values for a given tag', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = {
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
    };

    const formStore = new FormStore(resourceStore);
    formStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_area',
        },
        flag: {
            type: 'checkbox',
            tags: [
                {name: 'sulu.other'},
            ],
        },
    };

    expect(formStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Value 2']);
});

test('Return all the values for a given tag sorted by priority', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = {
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
    };

    const formStore = new FormStore(resourceStore);
    formStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part', priority: 10},
            ],
            type: 'text_line',
        },
        description: {
            tags: [
                {name: 'sulu.resource_locator_part', priority: 100},
            ],
            type: 'text_area',
        },
        flag: {
            type: 'checkbox',
        },
    };

    expect(formStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 2', 'Value 1']);
});

test('Return all the values for a given tag within sections', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = {
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
        article: 'Value 3',
    };

    const formStore = new FormStore(resourceStore);
    formStore.rawSchema = {
        highlight: {
            items: {
                title: {
                    tags: [
                        {name: 'sulu.resource_locator_part'},
                    ],
                    type: 'text_line',
                },
                description: {
                    tags: [
                        {name: 'sulu.resource_locator_part'},
                    ],
                    type: 'text_area',
                },
                flag: {
                    type: 'checkbox',
                },
            },
            type: 'section',
        },
        article: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_area',
        },
    };

    expect(formStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Value 2', 'Value 3']);
});

test('Return all the values for a given tag with empty blocks', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
    });

    const formStore = new FormStore(resourceStore);
    formStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    expect(formStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1']);
});

test('Return all the values for a given tag within blocks', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
        block: [
            {type: 'default', text: 'Block 1', description: 'Block Description 1'},
            {type: 'default', text: 'Block 2', description: 'Block Description 2'},
            {type: 'other', text: 'Block 3', description: 'Block Description 2'},
        ],
    });

    const formStore = new FormStore(resourceStore);
    formStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
                other: {
                    form: {
                        text: {
                            type: 'text_line',
                        },
                    },
                    title: 'Other',
                },
            },
        },
    };

    expect(formStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Block 1', 'Block 2']);
});

test('Return SchemaEntry for given schemaPath', () => {
    const formStore = new FormStore(new ResourceStore('test'));
    formStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    expect(formStore.getSchemaEntryByPath('/block/types/default/form/text')).toEqual({
        tags: [
            {name: 'sulu.resource_locator_part'},
        ],
        type: 'text_line',
    });
});

test('Remember fields being finished as modified fields and forget about them after saving', () => {
    const formStore = new FormStore(new ResourceStore('test'));
    formStore.rawSchema = {};
    formStore.finishField('/block/0/text');
    formStore.finishField('/block/0/text');
    formStore.finishField('/block/1/text');

    expect(formStore.isFieldModified('/block/0/text')).toEqual(true);
    expect(formStore.isFieldModified('/block/1/text')).toEqual(true);
    expect(formStore.isFieldModified('/block/2/text')).toEqual(false);

    return formStore.save().then(() => {
        expect(formStore.isFieldModified('/block/0/text')).toEqual(false);
        expect(formStore.isFieldModified('/block/1/text')).toEqual(false);
    });
});

test('Set new type after copying from different locale', () => {
    const schemaTypesPromise = Promise.resolve({
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    });

    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const resourceStore = new ResourceStore('test', 5);
    const formStore = new FormStore(resourceStore);

    resourceStore.copyFromLocale.mockReturnValue(Promise.resolve({template: 'sidebar'}));

    return schemaTypesPromise.then(() => {
        formStore.setType('footer');
        const promise = formStore.copyFromLocale('de');

        return promise.then(() => {
            expect(formStore.type).toEqual('sidebar');
        });
    });
});
