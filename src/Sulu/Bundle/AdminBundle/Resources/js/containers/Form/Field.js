// @flow
import React from 'react';
import {computed, isArrayLike} from 'mobx';
import {observer} from 'mobx-react';
import log from 'loglevel';
import jexl from 'jexl';
import Router from '../../services/Router';
import {translate} from '../../utils';
import Form from '../../components/Form';
import conditionDataProviderRegistry from './registries/conditionDataProviderRegistry';
import fieldRegistry from './registries/fieldRegistry';
import fieldStyles from './field.scss';
import FormInspector from './FormInspector';
import type {ChangeContext, Error, ErrorCollection, SchemaEntry} from './types';

type Props = {|
    data: Object,
    dataPath: string,
    error?: Error | ErrorCollection,
    formInspector: FormInspector,
    name: string,
    onChange: (name: string, value: *, context?: ChangeContext) => void,
    onFinish: (dataPath: string, schemaPath: string) => void,
    onSuccess: ?() => void,
    router: ?Router,
    schema: SchemaEntry,
    schemaPath: string,
    showAllErrors: boolean,
    value?: *,
|};

@observer
class Field extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    @computed get conditionData() {
        const {data, dataPath, formInspector} = this.props;

        return conditionDataProviderRegistry.getAll().reduce(
            function(data, conditionDataProvider) {
                return {...data, ...conditionDataProvider(data, dataPath, formInspector)};
            },
            {...data}
        );
    }

    @computed get disabled() {
        const {schema} = this.props;

        if (!schema.disabledCondition) {
            return false;
        }

        return jexl.evalSync(schema.disabledCondition, this.conditionData);
    }

    @computed get visible() {
        const {schema} = this.props;

        if (!schema.visibleCondition) {
            return true;
        }

        return jexl.evalSync(schema.visibleCondition, this.conditionData);
    }

    handleChange = (value: *, context?: ChangeContext) => {
        const {name, onChange} = this.props;

        if (this.disabled) {
            return;
        }

        onChange(name, value, context);
    };

    handleFinish = (subDataPath: ?string, subSchemaPath: ?string) => {
        const {dataPath, onFinish, schemaPath} = this.props;

        // if the fields are nested the field on every path should be finished
        if (subDataPath && subSchemaPath) {
            onFinish(subDataPath, subSchemaPath);
        }

        onFinish(dataPath, schemaPath);
    };

    handleFocus = (target: EventTarget) => {
        const {
            dataPath,
            schemaPath,
            schema: schemaEntry,
        } = this.props;

        const focusEvent = new Event('sulu.focus', {bubbles: true});
        // $FlowFixMe
        focusEvent.detail = {
            schemaType: schemaEntry?.type,
            setValue: this.handleChange,
            getValue: () => this.props.value,
            dataPath,
            schemaPath,
            formInspector: this.props.formInspector,
        };

        target.dispatchEvent(focusEvent);
    };

    findErrorKeyword(error: ?Error | ErrorCollection): ?string {
        if (!error) {
            return;
        }

        if (isArrayLike(error)) {
            // this happens when the error is in a block field type
            // since the error is shown on the child elements of the block we do not have to mark the block separately
            return;
        }

        if (error.keyword === 'const') {
            // the const validation only makes sense in combination with other dependant constraints, since it would
            // not make sense to have a field with a single value with no possibility to change it
            // therefore we are only showing the other errors, since the const error would just confuse users
            return;
        }

        if (typeof error.keyword === 'string') {
            return error.keyword;
        }

        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        for (const childKey in error) {
            // this happens when it is an error collection and not a single error
            // we will find the first child error with a keyword recursively
            // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
            return this.findErrorKeyword(error[childKey]);
        }
    }

    @computed.struct get types() {
        return this.props.schema.types;
    }

    render() {
        if (!this.visible) {
            return null;
        }

        const {
            data,
            dataPath,
            error,
            formInspector,
            name,
            onSuccess,
            router,
            schema,
            schemaPath,
            showAllErrors,
            value,
        } = this.props;

        const {
            defaultType,
            description,
            label,
            maxOccurs,
            minOccurs,
            onInvalid,
            options: schemaOptions = {},
            required,
            type,
        } = schema;

        let FieldType;

        try {
            FieldType = fieldRegistry.get(type);
        } catch (e) {
            if (onInvalid === 'ignore') {
                return null;
            }

            log.error(e);

            return (
                <Form.Field
                    colSpan={schema.colSpan}
                    spaceAfter={schema.spaceAfter}
                >
                    <div className={fieldStyles.fieldContainer}>
                        <div className={fieldStyles.field}>
                            <div className={fieldStyles.fieldException}>
                                <h4>Error while rendering field!</h4>
                                <p>
                                    <b>Name:</b> {name}<br />
                                    <b>Exception:</b> {e.toString()}
                                </p>
                            </div>
                        </div>
                    </div>
                </Form.Field>
            );
        }
        const fieldTypeOptions = fieldRegistry.getOptions(type);

        const errorKeyword = this.findErrorKeyword(error);

        return (
            <Form.Field
                colSpan={schema.colSpan}
                description={description}
                error={errorKeyword ? translate('sulu_admin.error_' + errorKeyword.toLowerCase()) : undefined}
                id={dataPath}
                label={label}
                required={required}
                spaceAfter={schema.spaceAfter}
            >
                <div className={fieldStyles.fieldContainer}>
                    <div className={fieldStyles.field}>
                        <FieldType
                            data={data}
                            dataPath={dataPath}
                            defaultType={defaultType}
                            disabled={this.disabled}
                            error={error}
                            fieldTypeOptions={fieldTypeOptions}
                            formInspector={formInspector}
                            label={label || name}
                            maxOccurs={maxOccurs}
                            minOccurs={minOccurs}
                            onChange={this.handleChange}
                            onFinish={this.handleFinish}
                            onFocus={this.handleFocus}
                            onSuccess={onSuccess}
                            router={router}
                            schemaOptions={schemaOptions}
                            schemaPath={schemaPath}
                            showAllErrors={showAllErrors}
                            types={this.types}
                            value={value}
                        />
                    </div>
                </div>
            </Form.Field>
        );
    }
}

export default Field;
