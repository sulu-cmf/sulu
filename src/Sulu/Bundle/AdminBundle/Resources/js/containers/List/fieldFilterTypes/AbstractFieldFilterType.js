// @flow
import type {Node} from 'react';
import {action, observable} from 'mobx';

export default class AbstractFieldFilterType<T> {
    onChange: (value: T) => void;
    parameters: ?{[string]: mixed};
    options: Object;
    @observable value: T;

    constructor(
        onChange: (value: T) => void,
        parameters: ?{[string]: mixed},
        value: T,
        options: Object = {}
    ) {
        this.onChange = onChange;
        this.parameters = parameters;
        this.value = value;
        this.options = options;
    }

    destroy() {}

    @action setValue(value: T): void {
        this.value = value;
    }

    confirm = (): void => {

    };

    // eslint-disable-next-line no-unused-vars
    getFormNode(): Node {
        return null;
    }

    // eslint-disable-next-line no-unused-vars
    getValueNode(value: T): Promise<Node> {
        return Promise.resolve(null);
    }
}
