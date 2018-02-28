// @flow
import React from 'react';
import {action, autorun, computed, observable, observe} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import {Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaSelectionOverlayStyles from './mediaSelectionOverlay.scss';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

type Props = {
    open: boolean,
    locale: IObservableValue<string>,
    excludedIds: Array<string | number>,
    onClose: () => void,
    onConfirm: (selectedMedia: Array<Object>) => void,
};

@observer
export default class MediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        open: false,
        excludedIds: [],
    };

    mediaPage: IObservableValue<number> = observable(1);
    collectionPage: IObservableValue<number> = observable(1);
    collectionId: IObservableValue<?string | number> = observable();
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    @observable collectionStore: CollectionStore;
    selectedMedia: Array<Object> = [];
    overlayDisposer: () => void;
    mediaSelectionsObservationDisposer: () => void;

    componentWillMount() {
        const {open} = this.props;

        if (open) {
            this.initialize();
        }
    }

    componentWillUnmount() {
        this.destroy();
    }

    componentWillReceiveProps(nextProps: Props) {
        const {open} = this.props;

        if (!open && nextProps.open) {
            this.initialize();
        }
    }

    @computed get locale(): IObservableValue<string> {
        return this.props.locale;
    }

    @action initialize() {
        this.createCollectionDatagridStore();
        this.createMediaDatagridStore();
        this.overlayDisposer = autorun(this.createCollectionStore);
        this.mediaPage.set(1);
        this.collectionPage.set(1);
    }

    @action destroy() {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        if (this.mediaDatagridStore) {
            this.mediaDatagridStore.destroy();
        }

        if (this.collectionDatagridStore) {
            this.collectionDatagridStore.destroy();
        }

        if (this.overlayDisposer) {
            this.overlayDisposer();
        }

        if (this.mediaSelectionsObservationDisposer) {
            this.mediaSelectionsObservationDisposer();
        }

        this.selectedMedia = [];
        this.collectionId.set(undefined);
    }

    @action setMediaPage(page: number) {
        this.mediaPage.set(page);
    }

    @action setCollectionPage(page: number) {
        this.collectionPage.set(page);
    }

    @action createCollectionDatagridStore() {
        this.collectionDatagridStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page: this.collectionPage,
                locale: this.locale,
                parent: this.collectionId,
            }
        );
    }

    createCollectionStore = () => {
        this.setCollectionStore(new CollectionStore(this.collectionId.get(), this.locale));
    };

    @action setCollectionStore(collectionStore: CollectionStore) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = collectionStore;
    }

    @action createMediaDatagridStore() {
        const {excludedIds} = this.props;
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

        if (excludedIds.length) {
            options.excluded = excludedIds.join(',');
        }

        this.mediaDatagridStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page: this.mediaPage,
                locale: this.locale,
                collection: this.collectionId,
            },
            options
        );

        this.mediaSelectionsObservationDisposer = observe(
            this.mediaDatagridStore.selections,
            this.handleMediaSelectionChanges
        );

        this.selectedMedia.forEach((media) => this.mediaDatagridStore.select(media.id));
    }

    handleMediaSelectionChanges = (change: any) => {
        const mediaId = (change.added.length) ? change.added[0] : change.removed[0];
        const selected = !!change.added.length;

        if (selected) {
            const media = this.mediaDatagridStore.data.find((entry) => entry.id === mediaId);

            if (media) {
                this.selectedMedia.push(media);
            }
        } else {
            this.selectedMedia = this.selectedMedia.filter((media) => media.id !== mediaId);
        }
    };

    @action handleCollectionNavigate = (collectionId: ?string | number) => {
        this.mediaDatagridStore.clearData();
        this.collectionDatagridStore.clearData();
        this.setMediaPage(1);
        this.setCollectionPage(1);
        this.collectionId.set(collectionId);
    };

    handleClose = () => {
        const {
            open,
            onClose,
        } = this.props;

        if (open) {
            this.destroy();
        }

        onClose();
    };

    handleSelectionReset = () => {
        this.selectedMedia = [];
        this.mediaDatagridStore.deselectEntirePage();
    };

    handleConfirm = () => {
        this.props.onConfirm(this.selectedMedia);
        this.destroy();
    };

    render() {
        const {
            open,
            locale,
        } = this.props;
        const actions = [
            {
                title: translate('sulu_media.reset_selection'),
                onClick: this.handleSelectionReset,
            },
        ];

        return (
            <Overlay
                open={open}
                title={translate('sulu_media.select_media')}
                onClose={this.handleClose}
                confirmText={translate('sulu_admin.confirm')}
                onConfirm={this.handleConfirm}
                actions={actions}
            >
                <div className={mediaSelectionOverlayStyles.overlay}>
                    <MediaCollection
                        locale={locale}
                        mediaDatagridAdapters={['media_card_selection']}
                        mediaDatagridStore={this.mediaDatagridStore}
                        collectionDatagridStore={this.collectionDatagridStore}
                        collectionStore={this.collectionStore}
                        onCollectionNavigate={this.handleCollectionNavigate}
                        overlayType="dialog"
                    />
                </div>
            </Overlay>
        );
    }
}
