// @flow
import React from 'react';
import Router from '../../services/Router';
import type {ErrorCollection} from '../Form/types';
import {FormInspector, Renderer} from '../Form';
import type {Schema} from '../Form';

type Props = {|
    data: Object,
    dataPath: string,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    index: number,
    onChange: (index: number, name: string, value: *) => void,
    onFieldFinish: ?() => void,
    onSuccess: ?() => void,
    router: ?Router,
    schema: Schema,
    schemaPath: string,
    showAllErrors: boolean,
    value: Object,
|};

export default class FieldRenderer extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    handleChange = (name: string, value: *) => {
        const {index, onChange} = this.props;
        onChange(index, name, value);
    };

    render() {
        const {
            data,
            dataPath,
            errors,
            formInspector,
            onFieldFinish,
            onSuccess,
            router,
            schema,
            schemaPath,
            showAllErrors,
            value,
        } = this.props;

        return (
            <Renderer
                data={data}
                dataPath={dataPath}
                errors={errors}
                formInspector={formInspector}
                onChange={this.handleChange}
                onFieldFinish={onFieldFinish}
                onSuccess={onSuccess}
                router={router}
                schema={schema}
                schemaPath={schemaPath}
                showAllErrors={showAllErrors}
                value={value}
            />
        );
    }
}
