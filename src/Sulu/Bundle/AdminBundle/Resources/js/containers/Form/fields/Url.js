// @flow
import React from 'react';
import type {FieldTypeProps} from '../../../types';
import UrlComponent from '../../../components/Url';

export default class Url extends React.Component<FieldTypeProps<?string>> {
    constructor(props: FieldTypeProps<?string>) {
        super(props);

        const {
            onChange,
            schemaOptions: {
                defaults: {
                    value: defaults,
                } = {},
            } = {},
            value,
        } = this.props;

        if (defaults !== undefined && !Array.isArray(defaults)) {
            throw new Error('The "defaults" schema option must be an array!');
        }

        const defaultSchemeOption = defaults && defaults.find((defaultOption) => defaultOption.name === 'scheme');
        const defaultSpecificPartOption = defaults && defaults.find(
            (defaultOption) => defaultOption.name === 'specific_part'
        );

        if (value || !defaultSpecificPartOption) {
            return;
        }

        if (!defaultSchemeOption) {
            throw new Error('It is not allowed to set a default URL without a scheme!');
        }

        if (typeof defaultSchemeOption.value !== 'string') {
            throw new Error('The "scheme" default must be a string if set!');
        }

        if (typeof defaultSpecificPartOption.value !== 'string') {
            throw new Error('The "specific_part" default must be a string if set!');
        }

        onChange(defaultSchemeOption.value + defaultSpecificPartOption.value);
    }

    handleBlur = () => {
        this.props.onFinish();
    };

    render() {
        const {
            dataPath,
            error,
            onChange,
            schemaOptions: {
                defaults: {
                    value: defaults = [
                        {name: 'scheme', value: 'https://'},
                    ],
                } = {},
                schemes: {
                    value: schemes = [
                        {name: 'http://'},
                        {name: 'https://'},
                        {name: 'ftp://'},
                        {name: 'ftps://'},
                    ],
                } = {},
            } = {},
            value,
        } = this.props;

        if (!Array.isArray(defaults) || defaults.length === 0) {
            throw new Error('The "defaults" option must contain scheme value!');
        }

        const defaultScheme = defaults.find((defaultOption) => defaultOption.name === 'scheme');

        if (!defaultScheme || typeof defaultScheme.value !== 'string') {
            throw new Error('No valid default scheme found in configuration!');
        }

        const defaultProtocol = defaultScheme.value;

        if (!Array.isArray(schemes) || schemes.length === 0) {
            throw new Error('The "schemes" schema option must contain some values!');
        }

        const protocols = schemes.map((scheme) => {
            if (typeof scheme.name !== 'string') {
                throw new Error(
                    'Every schema in the "schemes" schemaOption must contain a string string name'
                );
            }
            return scheme.name;
        });

        return (
            <UrlComponent
                defaultProtocol={defaultProtocol}
                id={dataPath}
                onBlur={this.handleBlur}
                onChange={onChange}
                protocols={protocols}
                valid={!error}
                value={value}
            />
        );
    }
}
