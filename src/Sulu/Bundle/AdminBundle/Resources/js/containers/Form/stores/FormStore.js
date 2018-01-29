// @flow
import {action, autorun, computed, observable, observe, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import ResourceStore from '../../../stores/ResourceStore';
import type {Schema, SchemaTypes} from '../types';
import metadataStore from './MetadataStore';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';

function addSchemaProperties(data: Object, key: string, schema: Schema) {
    const type = schema[key].type;

    if (type !== 'section') {
        data[key] = null;
    }

    const items = schema[key].items;

    if (type === 'section' && items) {
        Object.keys(items)
            .reduce((object, childKey) => addSchemaProperties(data, childKey, items), data);
    }

    return data;
}

export default class FormStore {
    resourceStore: ResourceStore;
    schema: Schema;
    @observable type: string;
    @observable types: SchemaTypes = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;

    constructor(resourceStore: ResourceStore) {
        this.resourceStore = resourceStore;

        metadataStore.getSchemaTypes(this.resourceStore.resourceKey)
            .then(this.handleSchemaTypeResponse);
    }

    destroy() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }

        if (this.typeDisposer) {
            this.typeDisposer();
        }
    }

    @action handleSchemaTypeResponse = (types: SchemaTypes) => {
        this.types = types;
        this.typesLoading = false;

        if (this.hasTypes) {
            // this will set the correct type from the server response after it has been loaded
            when(
                () => !this.resourceStore.loading,
                () => this.changeType(this.resourceStore.data[TYPE])
            );
        }

        this.schemaDisposer = autorun(() => {
            const {type} = this;

            if (this.hasTypes && !type) {
                return;
            }

            metadataStore.getSchema(this.resourceStore.resourceKey, type)
                .then(this.handleSchemaResponse);
        });
    };

    @action handleSchemaResponse = (schema: Schema) => {
        this.schema = schema;
        const schemaFields = Object.keys(schema)
            .reduce((data, key) => addSchemaProperties(data, key, schema), {});
        this.resourceStore.data = {...schemaFields, ...this.resourceStore.data};
        this.schemaLoading = false;

        if (this.hasTypes) {
            this.typeDisposer = observe(this, (change) => {
                if (change.name === 'type') {
                    this.resourceStore.set(TYPE, change.newValue);
                }
            });
        }
    };

    @computed get hasTypes(): boolean {
        return Object.keys(this.types).length > 0;
    }

    @computed get loading(): boolean {
        return this.resourceStore.loading || this.schemaLoading;
    }

    @computed get data(): Object {
        return this.resourceStore.data;
    }

    save() {
        this.resourceStore.save();
    }

    set(name: string, value: mixed) {
        this.resourceStore.set(name, value);
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.resourceStore.locale;
    }

    @action changeType(type: string) {
        if (Object.keys(this.types).length === 0) {
            throw new Error(
                'The resource "' + this.resourceStore.resourceKey + '" handled by this FormStore cannot handle types'
            );
        }

        this.type = type;
    }
}
