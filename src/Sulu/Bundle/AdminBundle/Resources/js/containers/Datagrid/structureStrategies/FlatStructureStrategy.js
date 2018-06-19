// @flow
import {action, computed, observable} from 'mobx';
import type {StructureStrategyInterface} from '../types';

export default class FlatStructureStrategy implements StructureStrategyInterface {
    @observable data: Array<Object>;

    @computed get visibleItems() {
        return this.data;
    }

    constructor() {
        this.data = [];
    }

    getData() {
        return this.data;
    }

    @action clear() {
        this.data.splice(0, this.data.length);
    }

    remove(identifier: string | number) {
        this.data.splice(this.data.findIndex((item) => item.id === identifier), 1);
    }

    findById(identifier: string | number): ?Object {
        // TODO do not hardcode id but use metdata instead
        return this.data.find((item) => item.id === identifier);
    }

    enhanceItem(item: Object): Object {
        return item;
    }
}
