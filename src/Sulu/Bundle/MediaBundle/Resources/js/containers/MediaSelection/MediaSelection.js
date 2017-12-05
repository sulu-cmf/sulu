// @flow
import React from 'react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/services';
import MediaSelectionStore from './stores/MediaSelectionStore';
import MediaSelectionOverlay from './MediaSelectionOverlay';
import MediaSelectionItem from './MediaSelectionItem';
import type {Value} from './types';

const ADD_ICON = 'plus';

type Props = {
    locale: IObservableValue<string>,
    value: ?Value,
    onChange: (value: Value) => void,
};

@observer
export default class MediaSelection extends React.PureComponent<Props> {
    mediaSelectionStore: MediaSelectionStore;
    @observable overlayOpen: boolean = false;

    componentWillMount() {
        const {
            value,
            locale,
        } = this.props;
        const selectedMediaIds = (value && value.ids) ? value.ids : null;

        this.mediaSelectionStore = new MediaSelectionStore(selectedMediaIds, locale);
    }

    @action openMediaOverlay() {
        this.overlayOpen = true;
    }

    @action closeMediaOverlay() {
        this.overlayOpen = false;
    }

    callChangeHandler() {
        this.props.onChange({
            ids: this.mediaSelectionStore.selectedMediaIds,
        });
    }

    getLabel(itemCount: number) {
        if (itemCount === 1) {
            return `1 ${translate('sulu_media.media_selected_singular')}`;
        } else if (itemCount > 1) {
            return `${itemCount} ${translate('sulu_media.media_selected_plural')}`;
        }

        return translate('sulu_media.select_media');
    }

    handleRemove = (mediaId: string | number) => {
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
        const {locale} = this.props;
        const {
            loading,
            selectedMedia,
            selectedMediaIds,
        } = this.mediaSelectionStore;
        const label = (loading) ? '' : this.getLabel(selectedMedia.length);

        return (
            <div>
                <MultiItemSelection
                    label={label}
                    loading={loading}
                    onItemRemove={this.handleRemove}
                    leftButton={{
                        icon: ADD_ICON,
                        onClick: this.handleOverlayOpen,
                    }}
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
                                key={id}
                                id={id}
                                index={index + 1}
                            >
                                <MediaSelectionItem thumbnail={thumbnail} mimeType={mimeType}>
                                    {title}
                                </MediaSelectionItem>
                            </MultiItemSelection.Item>
                        );
                    })}
                </MultiItemSelection>
                <MediaSelectionOverlay
                    open={this.overlayOpen}
                    locale={locale}
                    excludedIds={selectedMediaIds}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                />
            </div>
        );
    }
}
