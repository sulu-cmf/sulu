// @flow
import {action, autorun, computed, get, set, isArrayLike, observable, toJS, when} from 'mobx';
import jsonpointer from 'json-pointer';
import log from 'loglevel';
import {createAjv} from '../../../utils/Ajv';
import ResourceStore from '../../../stores/ResourceStore';
import AbstractFormStore, {SECTION_TYPE} from './AbstractFormStore';
import metadataStore from './metadataStore';
import type {ChangeContext, FormStoreInterface, Schema, SchemaEntry, SchemaType, SchemaTypes} from '../types';
import type {IObservableValue} from 'mobx/lib/mobx';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE_PROPERTY = 'template';

const ajv = createAjv();

function mergeData(
    localSchema: Schema,
    remoteSchema: Schema,
    localData: Object,
    remoteData: Object
) {
    let result = {};
    if (!localSchema || !remoteSchema) {
        return result;
    }

    for (const name in remoteSchema) {
        const {
            items: remoteItems,
            defaultType: remoteDefaultType,
            type: remoteType,
            types: remoteTypes,
        } = remoteSchema[name];
        const {
            items: localItems,
            defaultType: localDefaultType,
            type: localType,
            types: localTypes,
        } = localSchema[name] || {};

        if (remoteType === SECTION_TYPE && remoteItems) {
            result = mergeData(
                localSchema,
                remoteItems,
                localData,
                remoteData
            );
            continue;
        }

        if (localType === SECTION_TYPE && localItems) {
            result = mergeData(
                localItems,
                remoteSchema,
                localData,
                remoteData
            );
            continue;
        }
        if (remoteTypes && localTypes
            && Object.keys(remoteTypes).length > 0 && Object.keys(localTypes).length > 0
            && localData[name] && remoteData[name]
            && isArrayLike(localData[name]) && isArrayLike(remoteData[name])
        ) {
            for (let key = 0; key < Math.max(remoteData[name].length, localData[name].length); ++key) {
                const remoteChildData = toJS(remoteData[name].length > key ? remoteData[name][key] || {} : {});
                const localChildData = toJS(localData[name].length > key ? localData[name][key] || {} : {});

                const localChildDataType = localChildData?.type;
                const resultType = localChildDataType && localChildDataType in remoteTypes
                    ? localChildDataType
                    : remoteChildData?.type || remoteDefaultType;

                const localChildSchema =
                    // $FlowFixMe
                    localTypes[localChildData.type]?.form || localTypes[localDefaultType].form;

                const remoteChildSchema = remoteTypes[resultType].form;

                const resultChildData = mergeData(
                    localChildSchema,
                    remoteChildSchema,
                    localChildData,
                    remoteChildData
                );

                if (!result[name]) {
                    result[name] = [];
                }

                if (Object.keys(resultChildData).length > 0) {
                    resultChildData.type = resultType;
                    resultChildData.settings = localChildData?.settings || remoteChildData.settings;

                    result[name].push(resultChildData);
                }
            }

            continue;
        }

        if (localData[name] && remoteType === localType) {
            result[name] = localData[name];
        } else {
            result[name] = remoteData[name];
        }
    }

    return result;
}

export default class ResourceFormStore extends AbstractFormStore implements FormStoreInterface {
    resourceStore: ResourceStore;
    formKey: string;
    options: { [string]: any };
    @observable types: { [key: string]: SchemaType } = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    metadataOptions: ?{[string]: any};

    constructor(resourceStore: ResourceStore, formKey: string, options: Object = {}, metadataOptions: ?Object) {
        super();

        this.resourceStore = resourceStore;
        this.formKey = formKey;
        this.options = options;
        this.metadataOptions = metadataOptions;

        metadataStore.getSchemaTypes(this.formKey, this.metadataOptions)
            .then(this.handleSchemaTypeResponse);
    }

    destroy() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }
    }

    @action handleSchemaTypeResponse = (schemaTypes: ?SchemaTypes) => {
        const {
            types = {},
            defaultType,
        } = schemaTypes || {};

        this.types = types;
        this.typesLoading = false;

        if (this.hasTypes) {
            // set default type to the resource store if the loaded data does not contain a type
            when(
                () => !this.resourceStore.loading,
                (): void => {
                    const type = this.resourceStore.data[TYPE_PROPERTY] || defaultType || Object.keys(this.types)[0];
                    set(this.data, {[TYPE_PROPERTY]: type});
                }
            );
        }

        this.schemaDisposer = autorun(() => {
            if (this.hasTypes && !this.type) {
                this.setSchemaLoading(false);
                return;
            }

            if (this.hasTypes && this.type && !this.types[this.type]) {
                this.setSchemaLoading(false);
                return;
            }

            this.setSchemaLoading(true);
            Promise.all([
                metadataStore.getSchema(this.formKey, this.type, this.metadataOptions),
                metadataStore.getJsonSchema(this.formKey, this.type, this.metadataOptions),
            ]).then(this.handleSchemaResponse);
        });
    };

    handleSchemaResponse = ([schema, jsonSchema]: [Schema, Object]) => {
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.pathsByTag = {};

        return this.loadAndMergeRemoteData(this.schema, schema).then(action(() => {
            this.schema = schema;
            this.addMissingSchemaProperties();
            this.validate();
            this.setSchemaLoading(false);
        }));
    };

    loadAndMergeRemoteData = (localSchema: Schema, remoteSchema: Schema) => {
        // load data only after initial schema was set to prevent duplicate requests during initialization
        if (localSchema) {
            return this.resourceStore.requestRemoteData({template: this.type}).then((data: Object) => {
                const result = mergeData(localSchema, remoteSchema, this.data, data);
                this.setMultiple(result);
            });
        }
        return Promise.resolve();
    };

    @computed get hasTypes(): boolean {
        return Object.keys(this.types).length > 0;
    }

    @computed get hasInvalidType(): boolean {
        return !!this.types && !!this.type && !get(this.types, this.type);
    }

    @computed get loading(): boolean {
        return this.resourceStore.loading || this.schemaLoading;
    }

    @computed get data(): Object {
        return this.resourceStore.data;
    }

    @computed get type(): ?string {
        return this.hasTypes ? get(this.data, TYPE_PROPERTY) : undefined;
    }

    @action save(options: Object = {}): Promise<Object> {
        if (!this.validate()) {
            return Promise.reject('Errors occured when trying to save the data from the FormStore');
        }

        return this.resourceStore.save({...this.options, ...options}).then((response) => {
            const {modifiedFields} = this;
            modifiedFields.splice(0, modifiedFields.length);
            return response;
        }).catch((errorResponse) => {
            return errorResponse.json().then(action((error) => {
                return Promise.reject(error);
            }));
        });
    }

    delete(options: Object): Promise<Object> {
        return this.resourceStore.delete({...this.options, ...options});
    }

    copyFromLocale(sourceLocale: string, options: Object) {
        return this.resourceStore.copyFromLocale(sourceLocale, {
            ...options,
            ...this.options,
        });
    }

    /**
     * @deprecated
     */
    set(name: string, value: mixed) {
        log.warn(
            'The "set" method is deprecated and will be removed. ' +
            'Use the "change" method instead.'
        );

        this.resourceStore.set(name, value);
    }

    /**
     * @deprecated
     */
    setMultiple(data: Object) {
        log.warn(
            'The "setMultiple" method is deprecated and will be removed. ' +
            'Use the "changeMultiple" method instead.'
        );

        this.resourceStore.setMultiple(data);
    }

    change(dataPath: string, value: mixed, context?: ChangeContext) {
        if (context?.isDefaultValue || context?.isServerValue) {
            // set method of resource store will not mark the store as dirty
            this.resourceStore.set(dataPath, value);
        } else {
            this.resourceStore.change(dataPath, value);
        }
    }

    changeMultiple(values: {[dataPath: string]: mixed}, context?: ChangeContext) {
        if (context?.isDefaultValue || context?.isServerValue) {
            // setMultiple method of resource store will not mark the store as dirty
            this.resourceStore.setMultiple(values);
        } else {
            this.resourceStore.changeMultiple(values);
        }
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.resourceStore.locale;
    }

    @computed get resourceKey(): string {
        return this.resourceStore.resourceKey;
    }

    @computed get id(): ?string | number {
        return this.resourceStore.id;
    }

    @computed get saving(): boolean {
        return this.resourceStore.saving;
    }

    @computed get deleting(): boolean {
        return this.resourceStore.deleting;
    }

    @computed get forbidden(): boolean {
        return this.resourceStore.forbidden;
    }

    @computed get notFound(): boolean {
        return this.resourceStore.notFound;
    }

    @computed get unexpectedError(): boolean {
        return this.resourceStore.unexpectedError;
    }

    @computed get dirty(): boolean {
        return this.resourceStore.dirty;
    }

    set dirty(dirty: boolean) {
        this.resourceStore.dirty = dirty;
    }

    @action setSchemaLoading(schemaLoading: boolean) {
        this.schemaLoading = schemaLoading;
    }

    /**
     * @deprecated
     */
    @action setType(type: string) {
        log.warn(
            'The "setType" method is deprecated and will be removed. ' +
            'Use the "changeType" method instead.'
        );

        if (!this.hasTypes) {
            throw new Error(
                'The form "' + this.formKey + '" handled by this ResourceFormStore cannot handle types'
            );
        }

        this.set(TYPE_PROPERTY, type);
    }

    @action changeType(type: string, context?: ChangeContext) {
        if (!this.hasTypes) {
            throw new Error(
                'The form "' + this.formKey + '" handled by this ResourceFormStore cannot handle types'
            );
        }

        this.change(TYPE_PROPERTY, type, context);
    }

    getSchemaEntryByPath(schemaPath: string): SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }
}
