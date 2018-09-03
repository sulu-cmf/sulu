// @flow
import React from 'react';
import {autorun, toJS} from 'mobx';
import equals from 'fast-deep-equal';
import type {FieldTypeProps} from '../../../types';
import SmartContentComponent, {smartContentConfigStore, SmartContentStore} from '../../SmartContent';
import type {FilterCriteria} from '../../SmartContent/types';

type Props = FieldTypeProps<?FilterCriteria>;

const filterCriteriaDefaults = {
    audienceTargeting: undefined,
    categories: undefined,
    categoryOperator: undefined,
    dataSource: undefined,
    includeSubFolders: undefined,
    limitResult: undefined,
    presentAs: undefined,
    sortBy: undefined,
    sortMethod: undefined,
    tagOperator: undefined,
    tags: undefined,
};

export default class SmartContent extends React.Component<Props> {
    smartContentStore: SmartContentStore;

    constructor(props: Props) {
        super(props);

        const {
            formInspector,
            schemaOptions: {
                provider: {
                    value: provider,
                } = {value: 'pages'},
            } = {},
            value,
        } = this.props;

        if (typeof provider !== 'string') {
            throw new Error('The "provider" schemaOption must be a string, but received ' + typeof provider + '!');
        }

        const datasourceResourceKey = smartContentConfigStore.getConfig(provider).datasourceResourceKey;

        this.smartContentStore = new SmartContentStore(
            provider,
            value,
            formInspector.locale,
            datasourceResourceKey,
            formInspector.resourceKey === provider ? formInspector.id : undefined
        );

        autorun(this.handleFilterCriteriaChange);
    }

    componentWillUnmount() {
        this.smartContentStore.destroy();
    }

    handleFilterCriteriaChange = () => {
        const {onChange, onFinish, value} = this.props;

        const currentValue = {...filterCriteriaDefaults, ...toJS(value)};
        const newValue = {...filterCriteriaDefaults, ...toJS(this.smartContentStore.filterCriteria)};

        if (currentValue) {
            if (currentValue.categories) {
                currentValue.categories.sort();
            }

            if (currentValue.tags) {
                currentValue.tags.sort();
            }
        }

        if (newValue) {
            if (newValue.categories) {
                newValue.categories.sort();
            }

            if (newValue.tags) {
                newValue.tags.sort();
            }
        }

        if (this.smartContentStore.loading || equals(currentValue, newValue)) {
            return;
        }

        onChange(this.smartContentStore.filterCriteria);
        onFinish();
    };

    render() {
        const {
            label,
            schemaOptions: {
                present_as: {
                    value: schemaPresentations = [],
                } = {},
            } = {},
        } = this.props;

        if (!Array.isArray(schemaPresentations)) {
            throw new Error(
                'The "present_as" schemaOption must be a string, but received ' + typeof schemaPresentations + '!'
            );
        }

        const presentations = schemaPresentations.map((presentation) => {
            if (typeof presentation.name !== 'string' || typeof presentation.title !== 'string') {
                throw new Error(
                    'Every presentation in the "present_as" schemaOption must contain a string value and a string name'
                );
            }

            return {
                name: presentation.name,
                value: presentation.title,
            };
        });

        return (
            <SmartContentComponent
                fieldLabel={label}
                presentations={presentations}
                store={this.smartContentStore}
            />
        );
    }
}
