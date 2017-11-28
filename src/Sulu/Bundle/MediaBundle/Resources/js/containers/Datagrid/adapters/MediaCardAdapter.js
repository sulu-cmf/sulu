// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {Masonry} from 'sulu-admin-bundle/components';
import type {DatagridAdapterProps} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/services';
import MediaCard from '../../../components/MediaCard';

const THUMBNAIL_SIZE = 'sulu-240x';

type Props = DatagridAdapterProps & {
    icon: string,
    showCoverWhenSelected?: boolean,
};

@observer
export default class MediaCardAdapter extends React.Component<Props> {
    static formatFileSize(size: number) {
        const megaByteThreshold = 1000000;
        const kiloByteThreshold = 1000;

        if (size > 1000000) {
            return `${(size / megaByteThreshold).toFixed(2)} MB`;
        } else {
            return `${(size / kiloByteThreshold).toFixed(2)} KB`;
        }
    }

    getDownloadDropdownProps(item: Object) {
        const baseURL = window.location.origin;
        const {thumbnails} = item;
        const imageSizes = Object.keys(thumbnails).map((itemKey) => {
            return {
                url: baseURL + item.thumbnails[itemKey],
                label: itemKey,
            };
        });

        return {
            imageSizes,
            onDownload: this.handleDownload,
            downloadCopyText: translate('sulu_media.copy_url'),
            downloadUrl: baseURL + item.url,
            downloadText: translate('sulu_media.download_masterfile'),
        };
    }

    handleDownload = (downloadURL: string) => {
        window.location.href = downloadURL;
    };

    render() {
        const {
            data,
            icon,
            selections,
            onItemClick,
            onItemSelectionChange,
            showCoverWhenSelected,
        } = this.props;

        return (
            <Masonry>
                {data.map((item: Object) => {
                    const meta = `${item.mimeType} ${MediaCardAdapter.formatFileSize(item.size)}`;
                    const downloadDropdownProps = this.getDownloadDropdownProps(item);
                    const selected = selections.includes(item.id);

                    return (
                        // TODO: Don't access properties like "title" directly.
                        <MediaCard
                            {...downloadDropdownProps}
                            key={item.id}
                            id={item.id}
                            meta={meta}
                            icon={icon}
                            title={item.title}
                            image={item.thumbnails[THUMBNAIL_SIZE]}
                            onClick={onItemClick}
                            selected={selected}
                            onSelectionChange={onItemSelectionChange}
                            showCover={showCoverWhenSelected && selected}
                        />
                    );
                })}
            </Masonry>
        );
    }
}
