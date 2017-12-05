// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/services';
import {withToolbar, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaOverviewStyles from './mediaOverview.scss';

const MEDIA_ROUTE = 'sulu_media.form.detail';
const COLLECTION_ROUTE = 'sulu_media.overview';
const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

@observer
class MediaOverview extends React.PureComponent<ViewProps> {
    mediaPage: IObservableValue<number> = observable();
    collectionPage: IObservableValue<number> = observable();
    locale: IObservableValue<string> = observable();
    @observable collectionId: ?number;
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    collectionStore: CollectionStore;
    disposer: () => void;

    componentWillMount() {
        const {router} = this.props;

        this.mediaPage.set(1);

        router.bind('collectionPage', this.collectionPage, '1');
        router.bind('locale', this.locale);

        this.disposer = autorun(this.createStores);
    }

    componentWillUnmount() {
        const {router} = this.props;

        router.unbind('collectionPage', this.collectionPage);
        router.unbind('locale', this.locale);
        this.mediaDatagridStore.destroy();
        this.collectionDatagridStore.destroy();
        this.collectionStore.destroy();
        this.disposer();
    }

    getCollectionId() {
        const {router} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;

        return id;
    }

    createStores = () => {
        const collectionId = this.getCollectionId();

        if (collectionId !== this.collectionId || !this.collectionDatagridStore) {
            this.setCollectionId(collectionId);
            this.createCollectionStore(collectionId, this.locale);
            this.createMediaDatagridStore(collectionId, this.mediaPage, this.locale);
            this.createCollectionDatagridStore(collectionId, this.collectionPage, this.locale);
        }
    };

    @action setCollectionId(id) {
        this.collectionId = id;
    }

    @action createCollectionDatagridStore(collectionId, page, locale) {
        if (this.collectionDatagridStore) {
            this.collectionDatagridStore.destroy();
        }

        this.collectionDatagridStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page,
                locale,
            },
            (collectionId) ? {parent: collectionId} : undefined
        );
    }

    createCollectionStore = (collectionId, locale) => {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = new CollectionStore(collectionId, locale);
    };

    @action createMediaDatagridStore(collectionId, page, locale) {
        const options = {};

        options.limit = 50;
        options.fields = [
            'id',
            'type',
            'name',
            'size',
            'title',
            'mimeType',
            'subVersion',
            'thumbnails',
        ].join(',');

        page.set(1);

        if (collectionId) {
            options.collection = collectionId;
        }

        if (this.mediaDatagridStore) {
            this.mediaDatagridStore.destroy();
        }

        this.mediaDatagridStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page,
                locale,
            },
            options
        );
    }

    handleCollectionOpen = (collectionId) => {
        const {router} = this.props;
        router.navigate(
            COLLECTION_ROUTE,
            {
                id: collectionId,
                collectionPage: '1',
                locale: this.locale.get(),
            }
        );
    };

    handleMediaNavigate = (mediaId) => {
        const {router} = this.props;
        router.navigate(
            MEDIA_ROUTE,
            {
                id: mediaId,
                locale: this.locale.get(),
            }
        );
    };

    render() {
        return (
            <div className={mediaOverviewStyles.mediaOverview}>
                <MediaCollection
                    page={this.collectionPage}
                    locale={this.locale}
                    collectionStore={this.collectionStore}
                    mediaDatagridAdapters={['media_card_overview', 'table']}
                    mediaDatagridStore={this.mediaDatagridStore}
                    collectionDatagridStore={this.collectionDatagridStore}
                    onCollectionNavigate={this.handleCollectionOpen}
                    onMediaNavigate={this.handleMediaNavigate}
                />
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const router = this.props.router;
    const loading = this.collectionDatagridStore.loading || this.mediaDatagridStore.loading;

    const {
        route: {
            options: {
                locales,
            },
        },
    } = this.props.router;

    const locale = locales
        ? {
            value: this.locale.get(),
            onChange: action((locale) => {
                this.locale.set(locale);
            }),
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        locale,
        disableAll: loading,
        backButton: (this.collectionId)
            ? {
                onClick: () => {
                    router.restore(
                        COLLECTION_ROUTE,
                        {
                            id: this.collectionStore.parentId,
                            locale: this.locale.get(),
                            collectionPage: '1',
                        }
                    );
                },
            }
            : undefined,
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {},
            },
        ],
    };
});
