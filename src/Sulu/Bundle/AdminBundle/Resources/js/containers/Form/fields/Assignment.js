// @flow
import React from 'react';
import AssignmentComponent from '../../Assignment';
import type {FieldTypeProps} from '../../../types';

export default class Assignment extends React.Component<FieldTypeProps<Array<string | number>>> {
    render() {
        const {fieldOptions, onChange, value} = this.props;

        if (!fieldOptions || !fieldOptions.resourceKey) {
            throw new Error('The assignment field needs a "resourceKey" option to work properly');
        }

        const {icon, label, resourceKey, title} = fieldOptions;

        return (
            <AssignmentComponent
                icon={icon}
                label={label}
                onChange={onChange}
                resourceKey={resourceKey}
                title={title}
                value={value || []}
            />
        );
    }
}
