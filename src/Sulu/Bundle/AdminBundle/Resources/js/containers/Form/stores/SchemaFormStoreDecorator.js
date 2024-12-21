// @flow
import {action, computed, observable, when} from 'mobx';
import log from 'loglevel';
import metadataStore from './metadataStore';
import type {ChangeContext, FormStoreInterface, Schema, SchemaEntry} from '../types';

export default class SchemaFormStoreDecorator implements FormStoreInterface {
    @observable innerFormStore: ?FormStoreInterface;

    constructor(
        initializer: (schema: Schema, jsonSchema: Object) => FormStoreInterface,
        formKey: string,
        type: ?string,
        metadataOptions: ?{[string]: any}
    ) {
        Promise.all([
            metadataStore.getSchema(formKey, type, metadataOptions),
            metadataStore.getJsonSchema(formKey, type, metadataOptions),
        ]).then(action(([schema, jsonSchema]) => {
            this.innerFormStore = initializer(schema, jsonSchema);
        }));
    }

    change(dataPath: string, value: mixed, context?: ChangeContext) {
        when(
            () => !!this.innerFormStore,
            (): void => {
                this.innerFormStore?.change(dataPath, value, context);
            }
        );
    }

    changeType(type: string, context?: ChangeContext) {
        when(
            () => !!this.innerFormStore,
            (): void => {
                this.innerFormStore?.changeType(type, context);
            }
        );
    }

    changeMultiple(values: {[dataPath: string]: mixed}, context?: ChangeContext) {
        when(
            () => !!this.innerFormStore,
            (): void => {
                this.innerFormStore?.changeMultiple(values, context);
            }
        );
    }

    @computed get data() {
        if (this.innerFormStore) {
            return this.innerFormStore.data;
        }

        return {};
    }

    destroy() {
        when(
            () => !!this.innerFormStore,
            (): void => {
                this.innerFormStore?.destroy();
            }
        );
    }

    @computed get dirty() {
        if (this.innerFormStore) {
            return this.innerFormStore.dirty;
        }

        return false;
    }

    set dirty(dirty: boolean) {
        when(
            () => !!this.innerFormStore,
            (): void => {
                // $FlowFixMe
                this.innerFormStore.dirty = dirty;
            }
        );
    }

    @computed get errors() {
        if (this.innerFormStore) {
            return this.innerFormStore.errors;
        }

        return [];
    }

    @computed get hasErrors() {
        if (this.innerFormStore) {
            return this.innerFormStore.hasErrors;
        }

        return false;
    }

    @computed get forbidden() {
        if (this.innerFormStore) {
            return this.innerFormStore.forbidden;
        }

        return false;
    }

    @computed get notFound() {
        if (this.innerFormStore) {
            return this.innerFormStore.notFound;
        }

        return false;
    }

    @computed get unexpectedError() {
        if (this.innerFormStore) {
            return this.innerFormStore.unexpectedError;
        }

        return false;
    }

    finishField(dataPath: string) {
        when(
            () => !!this.innerFormStore,
            (): void => {
                this.innerFormStore?.finishField(dataPath);
            }
        );
    }

    getPathsByTag(tagName: string) {
        if (this.innerFormStore) {
            return this.innerFormStore.getPathsByTag(tagName);
        }

        return [];
    }

    getSchemaEntryByPath(schemaPath: string): ?SchemaEntry {
        if (this.innerFormStore) {
            return this.innerFormStore.getSchemaEntryByPath(schemaPath);
        }

        return undefined;
    }

    getValueByPath(dataPath: string): mixed {
        if (this.innerFormStore) {
            return this.innerFormStore.getValueByPath(dataPath);
        }

        return false;
    }

    getValuesByTag(tagName: string): Array<mixed> {
        if (this.innerFormStore) {
            return this.innerFormStore.getValuesByTag(tagName);
        }

        return [];
    }

    @computed get hasInvalidType() {
        if (this.innerFormStore) {
            return this.innerFormStore.hasInvalidType;
        }

        return false;
    }

    @computed get id() {
        if (this.innerFormStore) {
            return this.innerFormStore.id;
        }

        return undefined;
    }

    isFieldModified(dataPath: string): boolean {
        if (this.innerFormStore) {
            return this.innerFormStore.isFieldModified(dataPath);
        }

        return false;
    }

    @computed get loading() {
        if (this.innerFormStore) {
            return this.innerFormStore.loading;
        }

        return true;
    }

    set loading(loading: boolean) {
        when(
            () => !!this.innerFormStore,
            (): void => {
                // $FlowFixMe
                this.innerFormStore.loading = loading;
            }
        );
    }

    @computed get locale() {
        if (this.innerFormStore) {
            return this.innerFormStore.locale;
        }

        return undefined;
    }

    @computed get metadataOptions() {
        if (this.innerFormStore) {
            return this.innerFormStore.metadataOptions;
        }

        return undefined;
    }

    @computed get options() {
        if (this.innerFormStore) {
            return this.innerFormStore.options;
        }

        return {};
    }

    @computed get resourceKey() {
        if (this.innerFormStore) {
            return this.innerFormStore.resourceKey;
        }

        return undefined;
    }

    @computed.struct get schema(): Schema {
        if (this.innerFormStore) {
            return this.innerFormStore.schema;
        }

        return {};
    }

    @computed get types() {
        if (this.innerFormStore) {
            return this.innerFormStore.types;
        }

        return {};
    }

    validate() {
        if (this.innerFormStore) {
            return this.innerFormStore.validate();
        }

        return true;
    }

    /**
     * @deprecated
     */
    setType(type: string): void {
        log.warn(
            'The "setType" method is deprecated and will be removed. ' +
            'Use the "changeType" method instead.'
        );

        // the setType method was removed from the FormStoreInterface
        // we still want to call it to keep backwards compatibility if it is defined
        // $FlowFixMe
        if (this.innerFormStore && typeof this.innerFormStore.setType === 'function') {
            // $FlowFixMe
            return this.innerFormStore.setType(type);
        }
    }

    /**
     * @deprecated
     */
    setMultiple(data: Object) {
        log.warn(
            'The "setMultiple" method is deprecated and will be removed. ' +
            'Use the "changeMultiple" method instead.'
        );

        // the setMultiple method was removed from the FormStoreInterface
        // we still want to call it to keep backwards compatibility if it is defined
        // $FlowFixMe
        if (this.innerFormStore && typeof this.innerFormStore.setMultiple === 'function') {
            // $FlowFixMe
            this.innerFormStore.setMultiple(data);
        }
    }
}
