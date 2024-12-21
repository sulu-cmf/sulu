// @flow
import React from 'react';
import {observer, Observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Dropzone from 'react-dropzone';
import {SingleListOverlay} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import classNames from 'classnames';
import MediaUploadStore from '../../stores/MediaUploadStore';
import MediaItem from './MediaItem';
import DropzoneOverlay from './DropzoneOverlay';
import dropzoneStyles from './dropzone.scss';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef, Node} from 'react';

const COLLECTIONS_RESOURCE_KEY = 'collections';

type Props = {
    accept?: string,
    children: Node,
    className?: string,
    collectionId: ?string | number,
    disabled: boolean,
    locale: IObservableValue<string>,
    onClose: () => void,
    onOpen: () => void,
    onUpload: (media: Array<Object>) => void,
    onUploadError: (errors: Array<Object>) => void,
    open: boolean,
};

@observer
class MultiMediaDropzone extends React.Component<Props> {
    static defaultProps = {
        accept: undefined,
        disabled: false,
    };

    dropzoneRef: ElementRef<typeof Dropzone>;

    @observable filesScheduledForUpload: Array<File> = [];
    @observable mediaUploadStores: Array<MediaUploadStore> = [];

    setDropzoneRef = (ref: typeof Dropzone) => {
        this.dropzoneRef = ref;
    };

    @action addMediaUploadStore(mediaUploadStore: MediaUploadStore) {
        this.mediaUploadStores.push(mediaUploadStore);
    }

    @action destroyMediaUploadStores() {
        this.mediaUploadStores = [];
    }

    createMediaItems(): Array<*> {
        return this.mediaUploadStores.map((mediaUploadStore, index) => (
            <MediaItem key={index} store={mediaUploadStore} />
        ));
    }

    uploadFiles = (files: Array<File>, collectionId: string | number) => {
        const {
            locale,
            onClose,
            onUpload,
            onUploadError,
        } = this.props;
        const uploadPromises = [];

        files.forEach((file) => {
            const mediaUploadStore = new MediaUploadStore(undefined, locale);
            const uploadPromise = mediaUploadStore.create(collectionId, file);

            uploadPromises.push(uploadPromise);
            this.addMediaUploadStore(mediaUploadStore);
        });

        return Promise.allSettled(uploadPromises).then((results) => {
            const uploadedMedias = [];
            const errorResponses = [];

            results.forEach((result) => {
                if (result.status === 'fulfilled') {
                    uploadedMedias.push(result.value);
                } else {
                    errorResponses.push(result.reason);
                }
            });

            if (errorResponses.length === 0) {
                onUpload(uploadedMedias);
            } else {
                onUploadError(errorResponses);
            }

            setTimeout(() => {
                onClose();
                this.destroyMediaUploadStores();
            }, 1000);
        });
    };

    handleDragEnter = () => {
        this.props.onOpen();
    };

    handleDragLeave = () => {
        this.props.onClose();
    };

    handleDropzoneOverlayClose = () => {
        this.props.onClose();
    };

    @action handleDrop = (files: Array<File>) => {
        const {collectionId} = this.props;

        if (collectionId) {
            this.uploadFiles(files, collectionId);
        } else {
            this.filesScheduledForUpload = files;
        }
    };

    handleDropzoneOverlayClick = () => {
        this.dropzoneRef.open();
    };

    @action handleSelectCollectionOverlayClose = () => {
        this.filesScheduledForUpload = [];
        this.props.onClose();
    };

    @action handleSelectCollectionOverlayConfirm = (collection: Object) => {
        this.uploadFiles(this.filesScheduledForUpload, collection.id);
        this.filesScheduledForUpload = [];
    };

    render() {
        const {accept, children, className, disabled, locale, open} = this.props;

        const dropzoneClass = classNames(
            dropzoneStyles.dropzone,
            className
        );

        return (
            <>
                <Dropzone
                    accept={accept ? {[accept]: []} : undefined}
                    disabled={disabled}
                    noClick={true}
                    onDragEnter={this.handleDragEnter}
                    onDrop={this.handleDrop}
                    ref={this.setDropzoneRef}
                    style={{}} // to disable default style
                >
                    {({getInputProps, getRootProps}) => (
                        <Observer>
                            {() => (
                                <div {...getRootProps({className: dropzoneClass})}>
                                    {children}
                                    <input {...getInputProps()} />
                                    <DropzoneOverlay
                                        onClick={this.handleDropzoneOverlayClick}
                                        onClose={this.handleDropzoneOverlayClose}
                                        onDragLeave={this.handleDragLeave}
                                        open={open}
                                    >
                                        {this.createMediaItems()}
                                    </DropzoneOverlay>
                                </div>
                            )}
                        </Observer>
                    )}
                </Dropzone>
                <SingleListOverlay
                    adapter="column_list"
                    clearSelectionOnClose={true}
                    itemDisabledCondition="!!locked || (_permissions && !_permissions.add)"
                    listKey={COLLECTIONS_RESOURCE_KEY}
                    locale={locale}
                    onClose={this.handleSelectCollectionOverlayClose}
                    onConfirm={this.handleSelectCollectionOverlayConfirm}
                    open={this.filesScheduledForUpload.length > 0}
                    resourceKey={COLLECTIONS_RESOURCE_KEY}
                    title={translate('sulu_media.select_collection_for_upload')}
                />
            </>
        );
    }
}

export default MultiMediaDropzone;
