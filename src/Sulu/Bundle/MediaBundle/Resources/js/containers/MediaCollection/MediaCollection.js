// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Divider} from 'sulu-admin-bundle/components';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import CollectionStore from '../../stores/CollectionStore';
import MultiMediaDropzone from '../MultiMediaDropzone';
import type {OverlayType} from './types';
import CollectionSection from './CollectionSection';
import MediaSection from './MediaSection';

type Props = {|
    locale: IObservableValue<string>,
    mediaDatagridAdapters: Array<string>,
    mediaDatagridRef?: (?ElementRef<typeof Datagrid>) => void,
    mediaDatagridStore: DatagridStore,
    collectionDatagridStore: DatagridStore,
    collectionStore: CollectionStore,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    onMediaNavigate?: (mediaId: string | number) => void,
    overlayType: OverlayType,
|};

@observer
export default class MediaCollection extends React.Component<Props> {
    static defaultProps = {
        overlayType: 'overlay',
    };

    handleMediaClick = (mediaId: string | number) => {
        const {onMediaNavigate} = this.props;

        if (onMediaNavigate) {
            onMediaNavigate(mediaId);
        }
    };

    handleCollectionNavigate = (collectionId: ?string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleUpload = (media: Array<Object>) => {
        const {mediaDatagridStore} = this.props;

        mediaDatagridStore.reload();
        when(
            () => !mediaDatagridStore.loading,
            (): void => media.forEach((mediaItem) => mediaDatagridStore.select(mediaItem))
        );
    };

    render() {
        const {
            collectionDatagridStore,
            collectionStore,
            locale,
            overlayType,
            mediaDatagridAdapters,
            mediaDatagridRef,
            mediaDatagridStore,
        } = this.props;

        return (
            <MultiMediaDropzone
                collectionId={collectionStore.id}
                locale={locale}
                onUpload={this.handleUpload}
            >
                <CollectionSection
                    datagridStore={collectionDatagridStore}
                    locale={locale}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    overlayType={overlayType}
                    resourceStore={collectionStore.resourceStore}
                />
                <Divider />
                <div>
                    <MediaSection
                        adapters={mediaDatagridAdapters}
                        datagridStore={mediaDatagridStore}
                        mediaDatagridRef={mediaDatagridRef}
                        onMediaClick={this.handleMediaClick}
                    />
                </div>
            </MultiMediaDropzone>
        );
    }
}
