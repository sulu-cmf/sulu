// @flow
import React from 'react';
import log from 'loglevel';
import InputComponent from '../../../components/Input';
import type {FieldTypeProps} from '../../../types';

export default class Input extends React.Component<FieldTypeProps<?string>> {
    handleBlur = () => {
        this.props.onFinish();
    };

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
            disabled,
            onChange,
            schemaOptions: {
                headline: {
                    value: headline,
                } = {},
                max_characters: {
                    value: maxCharacters,
                } = {},
                soft_max_length: {
                    value: softMaxLength,
                } = {},
                max_segments: {
                    value: maxSegments,
                } = {},
                segment_delimiter: {
                    value: segmentDelimiter,
                } = {},
            } = {},
            value,
        } = this.props;

        if (headline !== undefined && typeof headline !== 'boolean') {
            throw new Error('The "headline" schema option must be a boolean!');
        }

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

        const evaluatedSoftMaxLength = softMaxLength || maxCharacters;

        if (maxSegments !== undefined && isNaN(maxSegments)) {
            throw new Error('The "max_segments" schema option must be a number!');
        }

        if (segmentDelimiter !== undefined && typeof segmentDelimiter !== 'string') {
            throw new Error('The "segment_delimiter" schema option must be a string!');
        }

        return (
            <InputComponent
                disabled={!!disabled}
                headline={headline}
                id={dataPath}
                maxCharacters={
                    evaluatedSoftMaxLength
                        ? parseInt(evaluatedSoftMaxLength)
                        : undefined
                }
                maxSegments={maxSegments ? parseInt(maxSegments) : undefined}
                onBlur={this.handleBlur}
                onChange={onChange}
                onFocus={this.handleFocus}
                segmentDelimiter={segmentDelimiter}
                valid={!error}
                value={value}
            />
        );
    }
}
