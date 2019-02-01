// @flow
import {action, computed, observable, set, toJS, untracked} from 'mobx';
import type {IObservableValue} from 'mobx';
import jexl from 'jexl';
import jsonpointer from 'jsonpointer';
import log from 'loglevel';
import type {RawSchema, RawSchemaEntry, Schema, SchemaEntry} from '../types';

const SECTION_TYPE = 'section';

function addSchemaProperties(data: Object, key: string, schema: RawSchema) {
    const type = schema[key].type;

    if (type !== SECTION_TYPE) {
        data[key] = undefined;
    }

    const items = schema[key].items;

    if (type === SECTION_TYPE && items) {
        Object.keys(items)
            .reduce((object, childKey) => addSchemaProperties(data, childKey, items), data);
    }

    return data;
}

function transformRawSchema(
    rawSchema: RawSchema,
    disabledFieldPaths: Array<string>,
    hiddenFieldPaths: Array<string>,
    basePath: string = ''
): Schema {
    return Object.keys(rawSchema).reduce((schema, schemaKey) => {
        schema[schemaKey] = transformRawSchemaEntry(
            rawSchema[schemaKey],
            disabledFieldPaths,
            hiddenFieldPaths,
            basePath + '/' + schemaKey
        );

        return schema;
    }, {});
}

function transformRawSchemaEntry(
    rawSchemaEntry: RawSchemaEntry,
    disabledFieldPaths: Array<string>,
    hiddenFieldPaths: Array<string>,
    path: string
): SchemaEntry {
    return Object.keys(rawSchemaEntry).reduce((schemaEntry, schemaEntryKey) => {
        if (schemaEntryKey === 'disabledCondition') {
            // jexl could be directly used here, if it would support synchrounous execution
            schemaEntry.disabled = disabledFieldPaths.includes(path);
        } else if (schemaEntryKey === 'visibleCondition') {
            // jexl could be directly used here, if it would support synchrounous execution
            schemaEntry.visible = !hiddenFieldPaths.includes(path);
        } else if (schemaEntryKey === 'items' && rawSchemaEntry.items) {
            schemaEntry.items = transformRawSchema(rawSchemaEntry.items, disabledFieldPaths, hiddenFieldPaths, path);
        } else if (schemaEntryKey === 'types' && rawSchemaEntry.types) {
            const rawSchemaEntryTypes = rawSchemaEntry.types;

            schemaEntry.types = Object.keys(rawSchemaEntryTypes).reduce((schemaEntryTypes, schemaEntryTypeKey) => {
                schemaEntryTypes[schemaEntryTypeKey] = {
                    title: rawSchemaEntryTypes[schemaEntryTypeKey].title,
                    form: transformRawSchema(
                        rawSchemaEntryTypes[schemaEntryTypeKey].form,
                        disabledFieldPaths,
                        hiddenFieldPaths,
                        path + '/types/' + schemaEntryTypeKey + '/form'
                    ),
                };

                return schemaEntryTypes;
            }, {});
        } else {
            // $FlowFixMe
            schemaEntry[schemaEntryKey] = rawSchemaEntry[schemaEntryKey];
        }

        return schemaEntry;
    }, {});
}

function evaluateFieldConditions(rawSchema: RawSchema, locale: ?string, data: Object, basePath: string = '') {
    const visibleConditionPromises = [];
    const disabledConditionPromises = [];

    Object.keys(rawSchema).forEach((schemaKey) => {
        const {disabledCondition, items, types, visibleCondition} = rawSchema[schemaKey];
        const schemaPath = basePath + '/' + schemaKey;

        const evaluationData = {...data, __locale: locale};

        if (disabledCondition) {
            disabledConditionPromises.push(jexl.eval(disabledCondition, evaluationData).then((result) => {
                if (result) {
                    return Promise.resolve(schemaPath);
                }
            }));
        }

        if (visibleCondition) {
            visibleConditionPromises.push(jexl.eval(visibleCondition, evaluationData).then((result) => {
                if (!result) {
                    return Promise.resolve(schemaPath);
                }
            }));
        }

        if (items) {
            const {
                disabledConditionPromises: itemDisabledConditionPromises,
                visibleConditionPromises: itemVisibleConditionPromises,
            } = evaluateFieldConditions(items, locale, data, schemaPath);

            disabledConditionPromises.push(...itemDisabledConditionPromises);
            visibleConditionPromises.push(...itemVisibleConditionPromises);
        }

        if (types) {
            Object.keys(types).forEach((type) => {
                const {
                    disabledConditionPromises: typeDisabledConditionPromises,
                    visibleConditionPromises: typeVisibleConditionPromises,
                } = evaluateFieldConditions(types[type].form, locale, data, schemaPath + '/types/' + type + '/form');

                disabledConditionPromises.push(...typeDisabledConditionPromises);
                visibleConditionPromises.push(...typeVisibleConditionPromises);
            });
        }
    });

    return {
        disabledConditionPromises,
        visibleConditionPromises,
    };
}

function sortObjectByPriority(a, b) {
    if (a.priority > b.priority) {
        return -1;
    }

    if (a.priority < b.priority) {
        return 1;
    }

    return 0;
}

function collectTagPathsWithPriority(
    tagName: string,
    data: Object,
    schema: RawSchema,
    parentPath: Array<string> = ['']
) {
    const pathsWithPriority = [];
    for (const key in schema) {
        const {items, tags, type, types} = schema[key];

        if (type === SECTION_TYPE && items) {
            pathsWithPriority.push(...collectTagPathsWithPriority(tagName, data, items, parentPath));
            continue;
        }

        if (types && Object.keys(types).length > 0 && data[key]) {
            for (const childKey of data[key].keys()) {
                const childData = data[key][childKey];
                pathsWithPriority.push(
                    ...collectTagPathsWithPriority(
                        tagName,
                        childData,
                        types[childData.type].form,
                        parentPath.concat([key, childKey])
                    )
                );
            }
            continue;
        }

        if (tags) {
            const filteredTags = tags.filter((tag) => tag.name === tagName);
            if (filteredTags.length === 0) {
                continue;
            }

            pathsWithPriority.push({
                path: parentPath.concat([key]).join('/'),
                priority: Math.max(...filteredTags.map((tag) => tag.priority || 0)),
            });
            continue;
        }
    }

    return pathsWithPriority.sort(sortObjectByPriority);
}

function collectTagPaths(
    tagName: string,
    data: Object,
    schema: RawSchema,
    parentPath: Array<string> = ['']
) {
    return collectTagPathsWithPriority(tagName, data, schema, parentPath)
        .map((pathWithPriority) => pathWithPriority.path);
}

export default class AbstractFormStore
{
    +data: Object;
    +loading: boolean;
    +locale: ?IObservableValue<string>;
    rawSchema: RawSchema;
    @observable disabledFieldPaths: Array<string> = [];
    @observable hiddenFieldPaths: Array<string> = [];
    modifiedFields: Array<string> = [];
    @observable errors: Object = {};
    validator: ?(data: Object) => boolean;
    pathsByTag: {[tagName: string]: Array<string>} = {};

    @computed.struct get schema(): Schema {
        return transformRawSchema(this.rawSchema, this.disabledFieldPaths, this.hiddenFieldPaths);
    }

    isFieldModified(dataPath: string): boolean {
        return this.modifiedFields.includes(dataPath);
    }

    finishField(dataPath: string): Promise<*> {
        if (!this.modifiedFields.includes(dataPath)) {
            this.modifiedFields.push(dataPath);
        }

        return this.updateFieldPathEvaluations();
    }

    @action validate() {
        const {validator} = this;
        const errors = {};

        if (validator && !validator(toJS(this.data))) {
            for (const error of validator.errors) {
                switch (error.keyword) {
                    case 'oneOf':
                        // this only happens if a block has an invalid child field
                        // child fields already show error messages so we do not have to do it again for blocks
                        break;
                    case 'required':
                        jsonpointer.set(
                            errors,
                            error.dataPath + '/' + error.params.missingProperty,
                            {keyword: error.keyword, parameters: error.params}
                        );
                        break;
                    default:
                        jsonpointer.set(
                            errors,
                            error.dataPath,
                            {keyword: error.keyword, parameters: error.params}
                        );
                }
            }
        }

        this.errors = errors;

        if (Object.keys(this.errors).length > 0) {
            log.info('Form validation detected the following errors: ', toJS(this.errors));
            return false;
        }

        return true;
    }

    updateFieldPathEvaluations = (): Promise<*> => {
        if (this.loading) {
            return Promise.resolve();
        }

        const {
            disabledConditionPromises,
            visibleConditionPromises,
        } = evaluateFieldConditions(
            this.rawSchema,
            this.locale ? this.locale.get() : undefined,
            untracked(() => toJS(this.data))
        );

        const disabledConditionsPromise = Promise.all(disabledConditionPromises)
            .then(action((disabledConditionResults) => {
                this.disabledFieldPaths = disabledConditionResults;
            }));

        const visibleConditionsPromise = Promise.all(visibleConditionPromises)
            .then(action((visibleConditionResults) => {
                this.hiddenFieldPaths = visibleConditionResults;
            }));

        return Promise.all([disabledConditionsPromise, visibleConditionsPromise]);
    };

    getValueByPath = (path: string): mixed => {
        return jsonpointer.get(this.data, path);
    };

    getValuesByTag(tagName: string): Array<mixed> {
        return this.getPathsByTag(tagName).map(this.getValueByPath);
    }

    getPathsByTag(tagName: string) {
        const {data, rawSchema} = this;
        if (!(tagName in this.pathsByTag)) {
            this.pathsByTag[tagName] = collectTagPaths(tagName, data, rawSchema);
        }

        return this.pathsByTag[tagName];
    }

    getSchemaEntryByPath(schemaPath: string): SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }

    @action addMissingSchemaProperties() {
        const schemaFields = Object.keys(this.schema)
            .reduce((data, key) => addSchemaProperties(data, key, this.rawSchema), {});
        set(this.data, {...schemaFields, ...this.data});
    }
}
