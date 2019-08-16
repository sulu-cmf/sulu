//@flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {List, ListStore, SingleListOverlay} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import {Button, ButtonGroup, Dialog, DropdownButton} from 'sulu-admin-bundle/components';
import CollectionFormOverlay from './CollectionFormOverlay';
import CollectionBreadcrumb from './CollectionBreadcrumb';
import PermissionFormOverlay from './PermissionFormOverlay';
import type {OperationType, OverlayType} from './types';
import collectionSectionStyles from './collectionSection.scss';

const COLLECTIONS_RESOURCE_KEY = 'collections';

type Props = {
    addable: boolean,
    deletable: boolean,
    editable: boolean,
    listStore: ListStore,
    locale: IObservableValue<string>,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    overlayType: OverlayType,
    resourceStore: ResourceStore,
    securable: boolean,
};

@observer
class CollectionSection extends React.Component<Props> {
    @observable openedCollectionOperationOverlayType: OperationType;

    @action openCollectionOperationOverlay(operationType: OperationType) {
        this.openedCollectionOperationOverlayType = operationType;
    }

    @action closeCollectionOperationOverlay() {
        this.openedCollectionOperationOverlayType = null;
    }

    @computed get collectionId(): ?number | string {
        const {resourceStore} = this.props;
        return resourceStore.id;
    }

    @computed get resourceStoreByOperationType(): ResourceStore {
        const {resourceStore, locale} = this.props;
        const {data} = resourceStore;

        if (this.openedCollectionOperationOverlayType === 'update') {
            return resourceStore.clone();
        }

        return new ResourceStore(
            COLLECTIONS_RESOURCE_KEY,
            null,
            {
                locale,
            },
            {
                depth: 1,
                breadcrumb: true,
                parent: data.parent,
            }
        );
    }

    handleCollectionClick = (collectionId: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleBreadcrumbNavigate = (collectionId?: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleAddCollectionClick = () => {
        this.openCollectionOperationOverlay('create');
    };

    handleEditCollectionClick = () => {
        this.openCollectionOperationOverlay('update');
    };

    handleRemoveCollectionClick = () => {
        this.openCollectionOperationOverlay('remove');
    };

    handleMoveCollectionClick = () => {
        this.openCollectionOperationOverlay('move');
    };

    handlePermissionCollectionClick = () => {
        this.openCollectionOperationOverlay('permissions');
    };

    handleCollectionOverlayConfirm = (resourceStore: ResourceStore) => {
        const options = {};
        options.breadcrumb = true;

        if (this.collectionId && this.openedCollectionOperationOverlayType === 'create') {
            options.parent = this.collectionId;
        }

        resourceStore.save(options)
            .then(() => this.handleSaveResponse(resourceStore));
    };

    handleSaveResponse = (resourceStore: ResourceStore) => {
        if (this.openedCollectionOperationOverlayType === 'update') {
            this.props.resourceStore.setMultiple(resourceStore.data);
        } else {
            this.props.onCollectionNavigate(resourceStore.id);
        }

        resourceStore.destroy();
        this.closeCollectionOperationOverlay();
    };

    handleCollectionOverlayClose = () => {
        this.closeCollectionOperationOverlay();
    };

    handlePermissionOverlayClose = () => {
        this.closeCollectionOperationOverlay();
    };

    handlePermissionOverlayConfirm = () => {
        const {resourceStore} = this.props;
        resourceStore.reload();
        this.closeCollectionOperationOverlay();
    };

    handleRemoveCollectionConfirm = () => {
        const {resourceStore} = this.props;
        const {data} = resourceStore;

        const parentCollectionId = data._embedded && data._embedded.parent && data._embedded.parent.id
            ? data._embedded.parent.id
            : undefined;

        resourceStore.delete()
            .then(() => {
                this.closeCollectionOperationOverlay();
                this.props.onCollectionNavigate(parentCollectionId);
            });
    };

    handleRemoveCollectionCancel = () => {
        this.closeCollectionOperationOverlay();
    };

    handleMoveCollectionConfirm = (collection: Object) => {
        const {resourceStore} = this.props;
        resourceStore.move(collection.id).then(() => {
            resourceStore.reload();
            this.closeCollectionOperationOverlay();
        });
    };

    handleMoveCollectionClose = () => {
        this.closeCollectionOperationOverlay();
    };

    render() {
        const {
            addable,
            deletable,
            editable,
            listStore,
            locale,
            overlayType,
            resourceStore,
            securable,
        } = this.props;

        const operationType = this.openedCollectionOperationOverlayType;

        return (
            <div>
                {!resourceStore.loading &&
                    <div className={collectionSectionStyles.collectionSection}>
                        <div className={collectionSectionStyles.left}>
                            <CollectionBreadcrumb
                                onNavigate={this.handleBreadcrumbNavigate}
                                resourceStore={resourceStore}
                            />
                        </div>

                        <div className={collectionSectionStyles.right}>
                            <ButtonGroup>
                                {addable &&
                                    <Button icon="su-plus" onClick={this.handleAddCollectionClick} />
                                }
                                {!!resourceStore.id && (editable || deletable || editable || securable) &&
                                    <DropdownButton icon="su-cog">
                                        {editable &&
                                            <DropdownButton.Item onClick={this.handleEditCollectionClick}>
                                                {translate('sulu_admin.edit')}
                                            </DropdownButton.Item>
                                        }
                                        {deletable &&
                                            <DropdownButton.Item onClick={this.handleRemoveCollectionClick}>
                                                {translate('sulu_admin.delete')}
                                            </DropdownButton.Item>
                                        }
                                        {editable &&
                                            <DropdownButton.Item onClick={this.handleMoveCollectionClick}>
                                                {translate('sulu_admin.move')}
                                            </DropdownButton.Item>
                                        }
                                        {securable &&
                                            <DropdownButton.Item onClick={this.handlePermissionCollectionClick}>
                                                {translate('sulu_security.permissions')}
                                            </DropdownButton.Item>
                                        }
                                    </DropdownButton>
                                }
                            </ButtonGroup>
                        </div>
                    </div>
                }
                <List
                    adapters={['folder']}
                    onItemClick={this.handleCollectionClick}
                    searchable={false}
                    store={listStore}
                />
                <CollectionFormOverlay
                    onClose={this.handleCollectionOverlayClose}
                    onConfirm={this.handleCollectionOverlayConfirm}
                    operationType={operationType}
                    overlayType={overlayType}
                    resourceStore={this.resourceStoreByOperationType}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={resourceStore.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleRemoveCollectionCancel}
                    onConfirm={this.handleRemoveCollectionConfirm}
                    open={operationType === 'remove'}
                    title={translate('sulu_media.remove_collection')}
                >
                    {translate('sulu_media.remove_collection_warning')}
                </Dialog>
                <PermissionFormOverlay
                    collectionId={this.collectionId}
                    onClose={this.handlePermissionOverlayClose}
                    onConfirm={this.handlePermissionOverlayConfirm}
                    open={operationType === 'permissions'}
                />
                <SingleListOverlay
                    adapter="column_list"
                    allowActivateForDisabledItems={false}
                    clearSelectionOnClose={true}
                    confirmLoading={resourceStore.moving}
                    disabledIds={resourceStore.id ? [resourceStore.id] : []}
                    listKey={COLLECTIONS_RESOURCE_KEY}
                    locale={locale}
                    onClose={this.handleMoveCollectionClose}
                    onConfirm={this.handleMoveCollectionConfirm}
                    open={operationType === 'move'}
                    options={{includeRoot: true}}
                    reloadOnOpen={true}
                    resourceKey={COLLECTIONS_RESOURCE_KEY}
                    title={translate('sulu_media.move_collection')}
                />
            </div>
        );
    }
}

export default CollectionSection;
