// @flow
import React from 'react';
import Dropzone from 'react-dropzone';
import Button from '../Button';
import type {Node} from 'react';
import type {ButtonSkin} from '../Button';

type Props = {|
    accept?: string,
    children?: Node,
    disabled: boolean,
    icon?: string,
    onUpload: (file: File) => void,
    skin?: ButtonSkin,
|};

export default class FileUploadButton extends React.Component<Props> {
    static defaultProps = {
        accept: undefined,
        disabled: false,
        icon: undefined,
        skin: undefined,
    };

    handleDrop = (files: Array<File>) => {
        const file = files[0];

        this.props.onUpload(file);
    };

    render() {
        const {children, disabled, icon, skin, accept} = this.props;

        return (
            <Dropzone
                accept={accept ? {[accept]: []} : undefined}
                onDrop={this.handleDrop}
                style={{}}
            >
                {({getInputProps, getRootProps}) => (
                    <div {...getRootProps()}>
                        <Button disabled={disabled} icon={icon} skin={skin}>
                            {children}
                        </Button>
                        <input {...getInputProps()} />
                    </div>
                )}
            </Dropzone>
        );
    }
}
