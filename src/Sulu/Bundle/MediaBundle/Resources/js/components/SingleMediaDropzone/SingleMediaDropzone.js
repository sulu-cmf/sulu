// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {observable, action} from 'mobx';
import classNames from 'classnames';
import Dropzone from 'react-dropzone';
import {CircularProgressbar, Icon} from 'sulu-admin-bundle/components';
import MimeTypeIndicator from '../MimeTypeIndicator';
import singleMediaDropzoneStyles from './singleMediaDropzone.scss';

const UPLOAD_ICON = 'fa-cloud-upload';

type Props = {|
    emptyIcon: string,
    image: ?string,
    mimeType: ?string,
    uploading: boolean,
    progress: number,
    onDrop: (data: File) => void,
    skin: 'default' | 'round',
    uploadText?: ?string,
|};

@observer
export default class SingleMediaDropzone extends React.Component<Props> {
    static defaultProps = {
        emptyIcon: 'su-image',
        mimeType: '',
        progress: 0,
        skin: 'default',
        uploading: false,
    };

    @observable uploadIndicatorVisibility: boolean;

    @action setUploadIndicatorVisibility(visibility: boolean) {
        this.uploadIndicatorVisibility = visibility;
    }

    handleDrop = (files: Array<File>) => {
        const file = files[0];

        this.props.onDrop(file);
        this.setUploadIndicatorVisibility(false);
    };

    handleDragEnter = () => {
        this.setUploadIndicatorVisibility(true);
    };

    handleDragLeave = () => {
        this.setUploadIndicatorVisibility(false);
    };

    render() {
        const {
            emptyIcon,
            image,
            mimeType,
            progress,
            skin,
            uploading,
            uploadText,
        } = this.props;

        const mediaContainerClass = classNames(
            singleMediaDropzoneStyles.mediaContainer,
            singleMediaDropzoneStyles[skin],
            {
                [singleMediaDropzoneStyles.showUploadIndicator]: this.uploadIndicatorVisibility,
            }
        );

        return (
            <Dropzone
                className={mediaContainerClass}
                disableClick={uploading}
                multiple={false}
                onDragEnter={this.handleDragEnter}
                onDragLeave={this.handleDragLeave}
                onDrop={this.handleDrop}
            >
                {image &&
                    <img className={singleMediaDropzoneStyles.thumbnail} src={image} />
                }
                {!image && mimeType &&
                    <div className={singleMediaDropzoneStyles.mimeTypeIndicator}>
                        <MimeTypeIndicator iconSize={100} mimeType={mimeType} />
                    </div>
                }
                {!image && !mimeType &&
                    <div className={singleMediaDropzoneStyles.emptyIndicator}>
                        <Icon name={emptyIcon} />
                    </div>
                }

                {!uploading
                    ? <div className={singleMediaDropzoneStyles.uploadIndicatorContainer}>
                        <div className={singleMediaDropzoneStyles.uploadIndicator}>
                            <div>
                                <Icon className={singleMediaDropzoneStyles.uploadIcon} name={UPLOAD_ICON} />
                                {uploadText &&
                                    <div className={singleMediaDropzoneStyles.uploadInfoText}>{uploadText}</div>
                                }
                            </div>
                        </div>
                    </div>
                    : <div className={singleMediaDropzoneStyles.progressbar}>
                        <CircularProgressbar
                            percentage={progress}
                            size={200}
                        />
                    </div>
                }
            </Dropzone>
        );
    }
}
