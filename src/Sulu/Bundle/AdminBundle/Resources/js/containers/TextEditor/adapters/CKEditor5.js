// @flow
import React from 'react';
import {isArrayLike} from 'mobx';
import CKEditor5Component from '../../CKEditor5';
import type {TextEditorProps} from '../types';
import type {IObservableArray} from 'mobx/lib/mobx';

export default class CKEditor5 extends React.Component<TextEditorProps> {
    render() {
        const {
            disabled,
            locale,
            onBlur,
            onChange,
            onFocus,
            options,
            value,
        } = this.props;

        const unvalidatedFormatOptionValues = options && options.formats ? options.formats.value : [];

        if (!isArrayLike(unvalidatedFormatOptionValues)) {
            throw new Error('The passed "formats" must be an array of strings');
        }
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        const formatOptionValues: Array<any> | IObservableArray<any> = unvalidatedFormatOptionValues;

        const formats = formatOptionValues.length
            ? formatOptionValues.map((format) => {
                if (typeof format.name !== 'string') {
                    throw new Error('The name property of the passed "formats" must be strings!');
                }
                return format.name;
            })
            : undefined;

        return (
            <CKEditor5Component
                disabled={disabled}
                formats={formats}
                locale={locale}
                onBlur={onBlur}
                onChange={onChange}
                onFocus={onFocus}
                value={value}
            />
        );
    }
}
