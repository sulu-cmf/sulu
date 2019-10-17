// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {ResourceRequester} from '../../services';

export default class SingleSelectionStore<T, U: {id: T} = Object> {
    @observable item: ?U;
    @observable loading: boolean = false;
    resourceKey: string;
    locale: ?IObservableValue<string>;
    options: Object;

    constructor(
        resourceKey: string,
        selectedItemId: ?T,
        locale: ?IObservableValue<string>,
        options: Object = {}
    ) {
        this.resourceKey = resourceKey;
        this.locale = locale;
        this.options = options;
        if (selectedItemId) {
            this.loadItem(selectedItemId);
        }
    }

    @action set(item: U) {
        this.item = item;
    }

    @action clear() {
        this.item = undefined;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action loadItem(itemId: ?T) {
        if (!itemId) {
            this.item = undefined;
            return;
        }

        this.setLoading(true);
        return ResourceRequester
            .get(this.resourceKey, {
                ...this.options,
                id: itemId,
                locale: this.locale ? this.locale.get() : undefined,
            })
            .then(action((data) => {
                this.item = data;
                this.setLoading(false);
            }))
            .catch(action((error) => {
                if (error.status !== 404) {
                    return Promise.reject(error);
                }

                this.item = null;
                this.setLoading(false);
            }));
    }
}
