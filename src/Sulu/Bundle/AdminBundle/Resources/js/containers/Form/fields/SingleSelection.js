// @flow
import React from 'react';
import {computed} from 'mobx';
import type {FieldTypeProps} from '../../../types';
import SingleAutoComplete from '../../../containers/SingleAutoComplete';

export default class SingleSelection extends React.Component<FieldTypeProps<?Object>>
{
    handleChange = (value: ?Object) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    @computed get type() {
        return this.props.fieldTypeOptions.default_type;
    }

    render() {
        const {
            fieldTypeOptions,
            value,
        } = this.props;

        if (this.type === 'single_auto_complete' && !fieldTypeOptions.types.single_auto_complete) {
            throw new Error(
                'The single_selection field needs an "single_auto_complete" type if rendered as SingleAutoComplete'
            );
        }

        const {
            resource_key: resourceKey,
            types: {
                single_auto_complete: {
                    display_property: displayProperty,
                    search_properties: searchProperties,
                },
            },
        } = fieldTypeOptions;

        return (
            <SingleAutoComplete
                displayProperty={displayProperty}
                searchProperties={searchProperties}
                onChange={this.handleChange}
                resourceKey={resourceKey}
                value={value}
            />
        );
    }
}
