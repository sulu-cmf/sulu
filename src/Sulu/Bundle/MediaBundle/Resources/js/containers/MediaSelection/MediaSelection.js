// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import MediaSelectionStore from './stores/MediaSelectionStore';
import MediaSelectionOverlay from './MediaSelectionOverlay';
import MediaSelectionItem from './MediaSelectionItem';
import type {Value} from './types';

@observer
export default class MediaSelection extends React.Component<FieldTypeProps<Value>> {
    mediaSelectionStore: MediaSelectionStore;
    @observable overlayOpen: boolean = false;

    constructor(props: FieldTypeProps<Value>) {
        super(props);

        const {
            formInspector,
            value,
        } = this.props;
        const selectedMediaIds = (value && value.ids) ? value.ids : null;

        if (!formInspector || !formInspector.locale) {
            throw new Error('The media selection needs a locale to work properly');
        }

        this.mediaSelectionStore = new MediaSelectionStore(selectedMediaIds, formInspector.locale);
    }

    @action openMediaOverlay() {
        this.overlayOpen = true;
    }

    @action closeMediaOverlay() {
        this.overlayOpen = false;
    }

    callChangeHandler() {
        const {onChange, onFinish} = this.props;

        onChange({
            ids: this.mediaSelectionStore.selectedMediaIds,
        });
        onFinish();
    }

    getLabel(itemCount: number) {
        if (itemCount === 1) {
            return `1 ${translate('sulu_media.media_selected_singular')}`;
        } else if (itemCount > 1) {
            return `${itemCount} ${translate('sulu_media.media_selected_plural')}`;
        }

        return translate('sulu_media.select_media');
    }

    handleRemove = (mediaId: number) => {
        this.mediaSelectionStore.removeById(mediaId);
        this.callChangeHandler();
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.mediaSelectionStore.move(oldItemIndex, newItemIndex);
        this.callChangeHandler();
    };

    handleOverlayOpen = () => {
        this.openMediaOverlay();
    };

    handleOverlayClose = () => {
        this.closeMediaOverlay();
    };

    handleOverlayConfirm = (selectedMedia: Array<Object>) => {
        selectedMedia.forEach((media) => this.mediaSelectionStore.add(media));
        this.callChangeHandler();
        this.closeMediaOverlay();
    };

    render() {
        const {formInspector} = this.props;

        if (!formInspector || !formInspector.locale) {
            throw new Error('The media selection needs a locale to work properly');
        }

        const {locale} = formInspector;

        const {
            loading,
            selectedMedia,
            selectedMediaIds,
        } = this.mediaSelectionStore;
        const label = (loading) ? '' : this.getLabel(selectedMedia.length);

        return (
            <Fragment>
                <MultiItemSelection
                    label={label}
                    leftButton={{
                        icon: 'su-image',
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                >
                    {selectedMedia.map((selectedMedia, index) => {
                        const {
                            id,
                            title,
                            mimeType,
                            thumbnail,
                        } = selectedMedia;

                        return (
                            <MultiItemSelection.Item
                                id={id}
                                index={index + 1}
                                key={id}
                            >
                                <MediaSelectionItem mimeType={mimeType} thumbnail={thumbnail}>
                                    {title}
                                </MediaSelectionItem>
                            </MultiItemSelection.Item>
                        );
                    })}
                </MultiItemSelection>
                <MediaSelectionOverlay
                    excludedIds={selectedMediaIds}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </Fragment>
        );
    }
}
