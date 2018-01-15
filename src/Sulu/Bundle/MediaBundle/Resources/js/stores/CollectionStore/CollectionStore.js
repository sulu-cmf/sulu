// @flow
import {computed} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {ResourceStore} from 'sulu-admin-bundle/stores';

const COLLECTIONS_RESOURCE_KEY = 'collections';

export default class CollectionStore {
    collectionId: ?string | number;
    locale: IObservableValue<string>;
    resourceStore: ResourceStore;

    constructor(collectionId: ?string | number, locale: IObservableValue<string>) {
        this.collectionId = collectionId;
        this.locale = locale;
        this.resourceStore = new ResourceStore(
            COLLECTIONS_RESOURCE_KEY,
            collectionId,
            {
                locale,
            },
            {
                depth: 1,
                breadcrumb: true,
                parent: true,
            }
        );
    }

    destroy() {
        this.resourceStore.destroy();
    }

    @computed get loading(): boolean {
        return this.resourceStore ? this.resourceStore.loading : false;
    }

    @computed get id(): ?string | number {
        return this.resourceStore.id;
    }

    @computed get parentId(): ?number {
        const {data} = this.resourceStore;

        if (!data._embedded) {
            return null;
        }

        const {
            _embedded: {
                parent,
            },
        } = data;

        return parent ? parent.id : null;
    }
}
