// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import CollectionStore from '../../stores/CollectionStore';
import MultiMediaDropzone from '../MultiMediaDropzone';
import type {OverlayType} from './types';
import CollectionSection from './CollectionSection';
import MediaSection from './MediaSection';

type Props = {|
    collectionListStore: ListStore,
    collectionStore: CollectionStore,
    locale: IObservableValue<string>,
    mediaListAdapters: Array<string>,
    mediaListRef?: (?ElementRef<typeof List>) => void,
    mediaListStore: ListStore,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    onMediaNavigate?: (mediaId: string | number) => void,
    onUploadOverlayClose: () => void,
    onUploadOverlayOpen: () => void,
    overlayType: OverlayType,
    uploadOverlayOpen: boolean,
|};

@observer
class MediaCollection extends React.Component<Props> {
    static defaultProps = {
        overlayType: 'overlay',
    };

    static addable: boolean = true;
    static deletable: boolean = true;
    static editable: boolean = true;
    static securable: boolean = true;

    handleMediaClick = (mediaId: string | number) => {
        const {onMediaNavigate} = this.props;

        if (onMediaNavigate) {
            onMediaNavigate(mediaId);
        }
    };

    handleCollectionNavigate = (collectionId: ?string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    @action handleUpload = (media: Array<Object>) => {
        const {mediaListStore} = this.props;

        mediaListStore.reset();
        mediaListStore.reload();

        when(
            () => !mediaListStore.loading,
            (): void => media.forEach((mediaItem) => mediaListStore.select(mediaItem))
        );
    };

    render() {
        const {
            collectionListStore,
            collectionStore,
            locale,
            overlayType,
            mediaListAdapters,
            mediaListRef,
            mediaListStore,
            onUploadOverlayClose,
            onUploadOverlayOpen,
            uploadOverlayOpen,
        } = this.props;

        const {permissions} = collectionStore;

        const addable = permissions.add !== undefined ? permissions.add : MediaCollection.addable;
        const editable = permissions.edit !== undefined ? permissions.edit : MediaCollection.editable;
        const deletable = permissions.delete !== undefined ? permissions.delete : MediaCollection.deletable;
        const securable = permissions.security !== undefined ? permissions.security : MediaCollection.securable;

        return (
            <MultiMediaDropzone
                collectionId={addable ? collectionStore.id : undefined}
                locale={locale}
                onClose={onUploadOverlayClose}
                onOpen={onUploadOverlayOpen}
                onUpload={this.handleUpload}
                open={uploadOverlayOpen}
            >
                <CollectionSection
                    addable={addable}
                    deletable={deletable}
                    editable={editable}
                    listStore={collectionListStore}
                    locale={locale}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    overlayType={overlayType}
                    resourceStore={collectionStore.resourceStore}
                    securable={securable}
                />
                <Divider />
                <div>
                    <MediaSection
                        adapters={mediaListAdapters}
                        listStore={mediaListStore}
                        mediaListRef={mediaListRef}
                        onMediaClick={this.handleMediaClick}
                    />
                </div>
            </MultiMediaDropzone>
        );
    }
}

export default MediaCollection;
