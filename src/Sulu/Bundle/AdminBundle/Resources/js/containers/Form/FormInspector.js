// @flow
import {computed} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {FinishFieldHandler} from './types';
import FormStore from './stores/FormStore';

export default class FormInspector {
    formStore: FormStore;

    finishFieldHandlers: Array<FinishFieldHandler> = [];

    constructor(formStore: FormStore) {
        this.formStore = formStore;
    }

    @computed get resourceKey(): string {
        return this.formStore.resourceKey;
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.formStore.locale;
    }

    @computed get options(): Object {
        return this.formStore.options;
    }

    @computed get errors(): Object {
        return this.formStore.errors;
    }

    @computed get id(): ?string | number {
        return this.formStore.id;
    }

    getValueByPath(path: string): mixed {
        return this.formStore.getValueByPath(path);
    }

    getValuesByTag(tagName: string): Array<mixed> {
        return this.formStore.getValuesByTag(tagName);
    }

    getSchemaEntryByPath(schemaPath: string) {
        return this.formStore.getSchemaEntryByPath(schemaPath);
    }

    addFinishFieldHandler(finishFieldHandler: FinishFieldHandler) {
        this.finishFieldHandlers.push(finishFieldHandler);
    }

    finishField(dataPath: string, schemaPath: string) {
        this.formStore.finishField(dataPath);
        this.finishFieldHandlers.forEach((finishFieldHandler) => finishFieldHandler(dataPath, schemaPath));
    }

    isFieldModified(dataPath: string): boolean {
        return this.formStore.isFieldModified(dataPath);
    }
}
