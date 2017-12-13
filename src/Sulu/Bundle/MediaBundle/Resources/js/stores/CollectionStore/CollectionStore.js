// @flow
import {action, autorun, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {BreadcrumbItem, BreadcrumbItems, Collection} from './types';

const COLLECTIONS_RESOURCE_KEY = 'collections';

export default class CollectionStore {
    @observable loading: boolean = false;
    @observable collection: Collection = {
        id: null,
        parentId: null,
        breadcrumb: null,
    };
    disposer: () => void;

    constructor(collectionId: ?string | number, locale: IObservableValue<string>) {
        this.disposer = autorun(() => {
            this.load(collectionId, locale.get());
        });
    }

    destroy() {
        this.disposer();
    }

    @computed get id(): ?number {
        return this.collection.id;
    }

    @computed get parentId(): ?number {
        return this.collection.parentId;
    }

    @computed get breadcrumb(): ?BreadcrumbItems {
        return this.collection.breadcrumb;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action load(collectionId: ?string | number, locale: string) {
        if (!collectionId) {
            this.collection.breadcrumb = null;

            return;
        }

        this.setLoading(true);

        return ResourceRequester.get(
            COLLECTIONS_RESOURCE_KEY,
            collectionId,
            {
                depth: 1,
                locale: locale,
                breadcrumb: true,
            }
        ).then(action((collectionInfo) => {
            const {
                _embedded: {
                    parent,
                    breadcrumb,
                },
            } = collectionInfo;
            const currentCollection = this.getCurrentCollectionItem(collectionInfo);

            this.collection.id = currentCollection.id;
            this.collection.parentId = (parent) ? parent.id : null;
            this.collection.breadcrumb = (breadcrumb) ? [...breadcrumb, currentCollection] : [currentCollection];

            this.setLoading(false);
        }));
    }

    getCurrentCollectionItem(data: Object): BreadcrumbItem {
        return {
            id: data.id,
            title: data.title,
        };
    }
}
