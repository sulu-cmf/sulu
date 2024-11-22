// @flow
import React from 'react';
import log from 'loglevel';
import TextAreaComponent from '../../../components/TextArea';
import type {FieldTypeProps} from '../types';

export default class TextArea extends React.Component<FieldTypeProps<?string>> {
    handleFocus = (event: Event) => {
        const {
            onFocus,
        } = this.props;

        if (onFocus) {
            onFocus(event.target);
        }
    };

    render() {
        const {
            dataPath,
            error,
            onChange,
            onFinish,
            disabled,
            schemaOptions: {
                max_characters: {
                    value: maxCharacters,
                } = {},
                soft_max_length: {
                    value: softMaxLength,
                } = {},
                rows: {
                    value: rows,
                } = {},
            } = {},
            value,
        } = this.props;

        if (maxCharacters !== undefined) {
            log.warn(
                'The "max_characters" schema option is deprecated since version 2.3 and will be removed. ' +
                'Use the "soft_max_length" option instead.'
            );
        }

        if (maxCharacters !== undefined && isNaN(maxCharacters)) {
            throw new Error('The "max_characters" schema option must be a number!');
        }

        if (softMaxLength !== undefined && isNaN(softMaxLength)) {
            throw new Error('The "soft_max_length" schema option must be a number!');
        }

        if (rows !== undefined && isNaN(rows)) {
            throw new Error('The "rows" schema option must be a number!');
        }

        const evaluatedSoftMaxLength = softMaxLength || maxCharacters;

        return (
            <TextAreaComponent
                disabled={!!disabled}
                id={dataPath}
                maxCharacters={evaluatedSoftMaxLength ? parseInt(evaluatedSoftMaxLength) : undefined}
                onBlur={onFinish}
                onChange={onChange}
                onFocus={this.handleFocus}
                rows={rows}
                valid={!error}
                value={value}
            />
        );
    }
}
