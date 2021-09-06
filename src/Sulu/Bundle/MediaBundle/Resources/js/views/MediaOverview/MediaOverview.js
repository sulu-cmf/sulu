// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {List, ListStore, SingleListOverlay, withToolbar} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaOverviewStyles from './mediaOverview.scss';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef} from 'react';

const COLLECTION_ROUTE = 'sulu_media.overview';
const MEDIA_ROUTE = 'sulu_media.form.details';

const COLLECTIONS_RESOURCE_KEY = 'collections';
const MEDIA_RESOURCE_KEY = 'media';

const USER_SETTINGS_KEY = 'media_overview';

@observer
class MediaOverview extends React.Component<ViewProps> {
    collectionPage: IObservableValue<number> = observable.box();
    mediaPage: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    collectionId: IObservableValue<?number | string> = observable.box();
    @observable errors: Array<string> = [];
    @observable mediaListStore: ListStore;
    @observable collectionListStore: ListStore;
    @observable collectionStore: CollectionStore;
    mediaList: ?ElementRef<typeof List>;
    @observable showMediaMoveOverlay: boolean = false;
    @observable showMediaUploadOverlay: boolean = false;
    @observable mediaMoving: boolean = false;
    disposer: () => void;

    static getDerivedRouteAttributes() {
        return {
            collectionLimit: ListStore.getLimitSetting(COLLECTIONS_RESOURCE_KEY, USER_SETTINGS_KEY),
            mediaFilter: ListStore.getFilterSetting(MEDIA_RESOURCE_KEY, USER_SETTINGS_KEY),
            mediaLimit: ListStore.getLimitSetting(MEDIA_RESOURCE_KEY, USER_SETTINGS_KEY),
            mediaSortColumn: ListStore.getSortColumnSetting(MEDIA_RESOURCE_KEY, USER_SETTINGS_KEY),
            mediaSortOrder: ListStore.getSortOrderSetting(MEDIA_RESOURCE_KEY, USER_SETTINGS_KEY),
        };
    }

    constructor(props: ViewProps) {
        super(props);

        const {router} = this.props;

        this.mediaPage.set(1);

        router.bind('collectionPage', this.collectionPage, 1);
        router.bind('mediaPage', this.mediaPage, 1);
        router.bind('locale', this.locale);
        router.bind('id', this.collectionId);

        this.disposer = autorun(this.createCollectionStore);

        this.createCollectionListStore();
        this.createMediaListStore();

        router.bind('search', this.mediaListStore.searchTerm);
        router.bind('collectionLimit', this.collectionListStore.limit, 10);
        router.bind('mediaFilter', this.mediaListStore.filterOptions, {});
        router.bind('mediaLimit', this.mediaListStore.limit, 10);
        router.bind('mediaSortColumn', this.mediaListStore.sortColumn);
        router.bind('mediaSortOrder', this.mediaListStore.sortOrder);
    }

    componentWillUnmount() {
        this.mediaListStore.destroy();
        this.collectionListStore.destroy();
        this.collectionStore.destroy();
        this.disposer();
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

    createCollectionListStore = () => {
        this.collectionListStore = new ListStore(
            COLLECTIONS_RESOURCE_KEY,
            COLLECTIONS_RESOURCE_KEY,
            USER_SETTINGS_KEY,
            {
                page: this.collectionPage,
                locale: this.locale,
                parentId: this.collectionId,
            }
        );

        this.collectionListStore.sort('title', 'asc');
    };

    createMediaListStore() {
        const options = {};

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

        this.mediaListStore = new ListStore(
            MEDIA_RESOURCE_KEY,
            MEDIA_RESOURCE_KEY,
            USER_SETTINGS_KEY,
            {
                page: this.mediaPage,
                locale: this.locale,
                collection: this.collectionId,
            },
            options
        );
    }

    clearLists() {
        this.mediaListStore.clear();
        this.mediaListStore.clearSelection();
        this.collectionListStore.clear();
        this.collectionListStore.clearSelection();
    }

    @action handleCollectionNavigate = (collectionId: ?string | number) => {
        this.clearLists();
        this.mediaPage.set(1);
        this.collectionPage.set(1);
        this.collectionId.set(collectionId);
    };

    @action handleUploadError = (errors: Array<Object>) => {
        if (errors.length === 1) {
            this.errors.push(errors[0].detail || errors[0].title || translate('sulu_media.upload_server_error'));
        } else {
            this.errors.push(translate('sulu_media.upload_server_error'));
        }
    };

    @action handleUploadOverlayOpen = () => {
        this.showMediaUploadOverlay = true;
    };

    @action handleUploadOverlayClose = () => {
        this.showMediaUploadOverlay = false;
    };

    handleMediaNavigate = (mediaId: string | number) => {
        const {router} = this.props;
        router.navigate(
            MEDIA_ROUTE,
            {
                id: mediaId,
                locale: this.locale.get(),
            }
        );
    };

    setMediaListRef = (mediaList: ?ElementRef<typeof List>) => {
        this.mediaList = mediaList;
    };

    @action handleMoveMediaOverlayClose = () => {
        this.showMediaMoveOverlay = false;
    };

    @action handleMoveMediaOverlayConfirm = (collection: Object) => {
        this.mediaMoving = true;

        this.mediaListStore.moveSelection(collection.id).then(action(() => {
            this.collectionListStore.reload();
            this.showMediaMoveOverlay = false;
            this.mediaMoving = false;
        }));
    };

    @action addError = (message: string): void => {
        this.errors.push(message);
    };

    render() {
        return (
            <>
                <MediaCollection
                    addError={this.addError}
                    className={mediaOverviewStyles.mediaCollection}
                    collectionListStore={this.collectionListStore}
                    collectionStore={this.collectionStore}
                    hideUploadAction={true}
                    locale={this.locale}
                    mediaListAdapters={['media_card_overview', 'table']}
                    mediaListRef={this.setMediaListRef}
                    mediaListStore={this.mediaListStore}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    onMediaNavigate={this.handleMediaNavigate}
                    onUploadError={this.handleUploadError}
                    onUploadOverlayClose={this.handleUploadOverlayClose}
                    onUploadOverlayOpen={this.handleUploadOverlayOpen}
                    uploadOverlayOpen={this.showMediaUploadOverlay}
                />
                <SingleListOverlay
                    adapter="column_list"
                    clearSelectionOnClose={true}
                    confirmLoading={this.mediaMoving}
                    disabledIds={this.collectionStore.id ? [this.collectionStore.id] : []}
                    itemDisabledCondition="!!locked"
                    listKey={COLLECTIONS_RESOURCE_KEY}
                    locale={this.locale}
                    onClose={this.handleMoveMediaOverlayClose}
                    onConfirm={this.handleMoveMediaOverlayConfirm}
                    open={this.showMediaMoveOverlay}
                    resourceKey={COLLECTIONS_RESOURCE_KEY}
                    title={translate('sulu_media.move_media')}
                />
            </>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const errors = this.errors;
    const router = this.props.router;
    const loading = this.collectionListStore.loading || this.mediaListStore.loading;

    const {
        route: {
            options: {
                locales,
                permissions: {
                    add: routeAddPermission,
                    delete: routeDeletePermission,
                    edit: routeEditPermission,
                },
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

    const items = [];

    const {
        permissions: collectionPermissions = {},
        loading: collectionLoading,
        locked: collectionLocked,
    } = this.collectionStore;

    const addPermission = collectionPermissions.add !== undefined ? collectionPermissions.add : routeAddPermission;
    const deletePermission = collectionPermissions.delete !== undefined
        ? collectionPermissions.delete
        : routeDeletePermission;
    const editPermission = collectionPermissions.edit !== undefined ? collectionPermissions.edit : routeEditPermission;

    if (!collectionLocked && addPermission) {
        items.push({
            disabled: collectionLoading,
            icon: 'su-upload',
            label: translate('sulu_media.upload_file'),
            onClick: action(() => {
                this.showMediaUploadOverlay = true;
            }),
            type: 'button',
        });
    }

    if (deletePermission) {
        items.push({
            disabled: this.mediaListStore.selectionIds.length === 0,
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete_selected'),
            loading: this.mediaListStore.deletingSelection,
            onClick: this.mediaList.requestSelectionDelete,
            type: 'button',
        });
    }

    if (!collectionLocked && editPermission) {
        items.push({
            disabled: this.mediaListStore.selectionIds.length === 0,
            icon: 'su-arrows-alt',
            label: translate('sulu_admin.move_selected'),
            onClick: action(() => {
                this.showMediaMoveOverlay = true;
            }),
            type: 'button',
        });
    }

    return {
        locale,
        disableAll: loading,
        backButton: this.collectionId.get()
            ? {
                onClick: () => {
                    this.clearLists();
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
        items,
        errors,
    };
});
