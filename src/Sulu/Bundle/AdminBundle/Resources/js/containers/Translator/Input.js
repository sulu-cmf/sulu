// @flow
import React from 'react';
import {TextEditor} from '../../containers';
import translatorStyles from './translator.scss';

type Props = {|
    onChange?: (text: string) => void,
    text: string,
    type: 'text_line' | 'text_area' | 'text_editor',
|};

/**
 * @internal
 */
export default class Input extends React.Component<Props> {
    handleChange = (value: ?string) => {
        const {
            onChange,
        } = this.props;

        if (onChange) {
            onChange(value || '');
        }
    };

    handleTextAreChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.handleChange(event.currentTarget.value);
    };

    render() {
        const {
            type,
        } = this.props;

        if (type === 'text_editor') {
            return this.renderEditor();
        }

        return this.renderTextarea();
    }

    renderEditor() {
        const {
            text,
            onChange,
        } = this.props;

        return (
            <div className={translatorStyles.input + ' ' + translatorStyles.texteditor}>
                <TextEditor
                    adapter="ckeditor5"
                    disabled={onChange === undefined}
                    locale={undefined}
                    onChange={this.handleChange}
                    value={text}
                />
            </div>
        );
    }

    renderTextarea() {
        const {
            text,
        } = this.props;

        return (
            <textarea
                className={translatorStyles.input + ' ' + translatorStyles.textarea}
                onChange={this.handleTextAreChange}
                value={text}
            />
        );
    }
}
