// @flow
import React from 'react';
import {observable} from 'mobx';
import TextEditorContainer from '../../../containers/TextEditor';
import userStore from '../../../stores/userStore';
import type {FieldTypeProps} from '../types';

export default class TextEditor extends React.Component<FieldTypeProps<?string>> {
    handleFocus = (event: { target: EventTarget }) => {
        const {
            onFocus,
        } = this.props;

        if (onFocus) {
            onFocus(event.target);
        }
    };

    render() {
        const {disabled, formInspector, onChange, onFinish, schemaOptions, value} = this.props;

        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        return (
            <TextEditorContainer
                adapter="ckeditor5"
                disabled={!!disabled}
                locale={locale}
                onBlur={onFinish}
                onChange={onChange}
                onFocus={this.handleFocus}
                options={schemaOptions}
                value={value}
            />
        );
    }
}
