// @flow
import {action, computed, isArrayLike, observable, set, toJS} from 'mobx';
import jsonpointer from 'json-pointer';
import log from 'loglevel';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {Schema, SchemaEntry} from '../types';

export const SECTION_TYPE = 'section';

function addSchemaProperties(data: Object, key: string, schema: Schema) {
    const type = schema[key].type;

    if (type !== SECTION_TYPE) {
        jsonpointer.set(data, '/' + key, undefined);
    }

    const items = schema[key].items;

    if (type === SECTION_TYPE && items) {
        Object.keys(items)
            .reduce((object, childKey) => addSchemaProperties(data, childKey, items), data);
    }

    return data;
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
    schema: Schema,
    parentPath: Array<string> = ['']
) {
    const pathsWithPriority = [];
    for (const key in schema) {
        const {items, tags, type, types} = schema[key];

        if (type === SECTION_TYPE && items) {
            pathsWithPriority.push(...collectTagPathsWithPriority(tagName, data, items, parentPath));
            continue;
        }

        if (types
            && Object.keys(types).length > 0
            && data[key]
            && (isArrayLike(data[key]))
        ) {
            for (const childKey of data[key].keys()) {
                const childData = data[key][childKey];

                if (childData.type in types) {
                    pathsWithPriority.push(
                        ...collectTagPathsWithPriority(
                            tagName,
                            childData,
                            types[childData.type].form,
                            parentPath.concat([key, childKey])
                        )
                    );
                }
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
    schema: Schema,
    parentPath: Array<string> = ['']
) {
    return collectTagPathsWithPriority(tagName, data, schema, parentPath)
        .map((pathWithPriority) => pathWithPriority.path);
}

export default class AbstractFormStore
{
    +data: {[string]: any};
    +options: {[string]: any};
    +metadataOptions: ?{[string]: any};
    +loading: boolean;
    +locale: ?IObservableValue<string>;
    @observable schema: Schema;
    modifiedFields: Array<string> = [];
    @observable errors: Object = {};
    validator: ?(data: Object) => boolean;
    pathsByTag: {[tagName: string]: Array<string>} = {};

    get forbidden(): boolean {
        return false;
    }

    get notFound(): boolean {
        return false;
    }

    get unexpectedError(): boolean {
        return false;
    }

    isFieldModified(dataPath: string): boolean {
        return this.modifiedFields.includes(dataPath);
    }

    finishField(dataPath: string) {
        if (!this.modifiedFields.includes(dataPath)) {
            this.modifiedFields.push(dataPath);
        }
    }

    @action validate() {
        const {validator} = this;
        const errors = {};

        if (validator && !validator(toJS(this.data))) {
            // $FlowFixMe
            for (const error of validator.errors) {
                switch (error.keyword) {
                    case 'type':
                    case 'if':
                    case 'then':
                    case 'else':
                    case 'oneOf':
                    case 'anyOf':
                    case 'allOf':
                        // these errors are not shown in the leaf field, e.g. in blocks and similar constructs
                        // these errors also have child errors, which will be shown on the correct leaf field
                        break;
                    case 'required':
                        jsonpointer.set(
                            errors,
                            error.instancePath + '/' + error.params.missingProperty,
                            {keyword: error.keyword, parameters: error.params}
                        );
                        break;
                    default:
                        jsonpointer.set(
                            errors,
                            error.instancePath,
                            {keyword: error.keyword, parameters: error.params}
                        );
                }
            }
        }

        this.errors = errors;

        if (this.hasErrors) {
            log.info('Form validation detected the following errors: ', toJS(this.errors));
            return false;
        }

        return true;
    }

    @computed get hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    getValueByPath = (dataPath: string): mixed => {
        return jsonpointer.has(this.data, dataPath) ? jsonpointer.get(this.data, dataPath) : undefined;
    };

    getValuesByTag(tagName: string): Array<mixed> {
        return this.getPathsByTag(tagName).map(this.getValueByPath);
    }

    getPathsByTag(tagName: string) {
        const {data, schema} = this;
        if (!(tagName in this.pathsByTag)) {
            this.pathsByTag[tagName] = collectTagPaths(tagName, data, schema);
        }

        return this.pathsByTag[tagName];
    }

    getSchemaEntryByPath(schemaPath: string): ?SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }

    @action addMissingSchemaProperties() {
        const schemaFields = Object.keys(this.schema)
            .reduce((data, key) => addSchemaProperties(data, key, this.schema), {});
        set(this.data, {...schemaFields, ...this.data});
    }

    destroy() {}
}
