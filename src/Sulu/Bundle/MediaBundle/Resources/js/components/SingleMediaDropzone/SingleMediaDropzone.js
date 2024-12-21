// @flow
import React, {Fragment} from 'react';
import {observer, Observer} from 'mobx-react';
import {observable, action} from 'mobx';
import classNames from 'classnames';
import Dropzone from 'react-dropzone';
import {CircularProgressbar, Icon, Loader} from 'sulu-admin-bundle/components';
import MimeTypeIndicator from '../MimeTypeIndicator';
import singleMediaDropzoneStyles from './singleMediaDropzone.scss';

const UPLOAD_ICON = 'su-upload';

type Props = {|
    accept?: string,
    disabled: boolean,
    emptyIcon: string,
    errorText?: ?string,
    image: ?string,
    mimeType: ?string,
    onDrop: (data: File) => void,
    progress: number,
    skin: 'default' | 'round',
    uploading: boolean,
    uploadText?: ?string,
|};

@observer
class SingleMediaDropzone extends React.Component<Props> {
    static defaultProps = {
        accept: undefined,
        disabled: false,
        emptyIcon: 'su-image',
        mimeType: '',
        progress: 0,
        skin: 'default',
        uploading: false,
    };

    image: Image;

    @observable uploadIndicatorVisibility: boolean;
    @observable imageLoading: boolean = false;
    @observable imageError: boolean = false;

    componentDidMount() {
        this.preloadImage();
    }

    componentDidUpdate(prevProps: Props) {
        if (this.props.image !== prevProps.image) {
            this.preloadImage();
        }
    }

    @action preloadImage() {
        const {image: src} = this.props;

        if (src) {
            this.imageLoading = true;

            this.image = new Image();
            this.image.onerror = this.handleImageError;
            this.image.onload = this.handleImageLoad;
            this.image.src = src;
        } else {
            this.handleImageLoad();
        }
    }

    @action handleImageLoad = () => {
        this.imageLoading = false;
        this.imageError = false;
    };

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

    @action handleImageError = () => {
        this.imageError = true;
    };

    render() {
        const {
            accept,
            disabled,
            emptyIcon,
            errorText,
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
                [singleMediaDropzoneStyles.disabled]: disabled,
            }
        );

        return (
            <>
                <Dropzone
                    accept={accept ? {[accept]: []} : undefined}
                    disabled={disabled}
                    multiple={false}
                    noClick={uploading}
                    onDragEnter={this.handleDragEnter}
                    onDragLeave={this.handleDragLeave}
                    onDrop={this.handleDrop}
                >
                    {({getInputProps, getRootProps}) => (
                        <Observer>
                            {() => (
                                <div {...getRootProps({className: mediaContainerClass})}>
                                    {image && !this.imageError &&
                                        <Fragment>
                                            <img
                                                className={singleMediaDropzoneStyles.thumbnail}
                                                key={image}
                                                src={image}
                                            />
                                            {this.imageLoading && <Loader />}
                                        </Fragment>
                                    }
                                    {(!image || this.imageError) && mimeType &&
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
                                                    <Icon
                                                        className={singleMediaDropzoneStyles.uploadIcon}
                                                        name={UPLOAD_ICON}
                                                    />
                                                    {uploadText &&
                                                        <div className={singleMediaDropzoneStyles.uploadInfoText}>
                                                            {uploadText}
                                                        </div>
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
                                    <input {...getInputProps()} />
                                </div>
                            )}
                        </Observer>
                    )}
                </Dropzone>
                {errorText && (
                    <div className={singleMediaDropzoneStyles.errorText}>{errorText}</div>
                )}
            </>
        );
    }
}

export default SingleMediaDropzone;
