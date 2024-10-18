// @flow
import React from 'react';
import {computed, toJS, reaction, when, isObservableArray} from 'mobx';
import equals from 'fast-deep-equal';
import jsonpointer from 'json-pointer';
import SmartContentComponent, {smartContentConfigStore, SmartContentStore} from '../../SmartContent';
import smartContentStorePool from './smartContentStorePool';
import type {FieldTypeProps} from '../../../types';
import type {FilterCriteria, Presentation} from '../../SmartContent/types';

type Props = FieldTypeProps<?FilterCriteria>;

class SmartContent extends React.Component<Props> {
    smartContentStore: SmartContentStore;
    filterCriteriaChangeDisposer: () => mixed;

    @computed get previousSmartContentStores() {
        return smartContentStorePool.findPreviousStores(this.smartContentStore);
    }

    @computed get presentations(): Array<Presentation> {
        const {
            schemaOptions: {
                present_as: {
                    value: schemaPresentations = [],
                } = {},
            } = {},
        } = this.props;

        if (!(Array.isArray(schemaPresentations) || isObservableArray(schemaPresentations))) {
            throw new Error(
                'The "present_as" schemaOption must be an array, but received ' + typeof schemaPresentations + '!'
            );
        }

        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        return schemaPresentations.map((presentation) => {
            const {name, title} = presentation;

            if (!name) {
                throw new Error('Every presentation in the "present_as" schema Option must contain a name');
            }

            if (!title) {
                throw new Error('Every presentation in the "present_as" schema Option must contain a title');
            }

            return {
                name: name.toString(),
                value: title.toString(),
            };
        });
    }

    @computed get provider() {
        const {
            schemaOptions: {
                provider: {
                    value: provider,
                } = {value: 'pages'},
            } = {},
        } = this.props;

        if (typeof provider !== 'string') {
            throw new Error('The "provider" schemaOption must be a string, but received ' + typeof provider + '!');
        }

        return provider;
    }

    @computed get value() {
        const {value} = this.props;

        return value !== undefined
            ? value
            : this.defaultValue;
    }

    @computed get defaultValue() {
        return smartContentConfigStore.getDefaultValue(
            this.provider,
            this.presentations
        );
    }

    @computed get viewName() {
        return smartContentConfigStore.getConfig(this.provider).view;
    }

    @computed get resultToView() {
        return smartContentConfigStore.getConfig(this.provider).resultToView;
    }

    constructor(props: Props) {
        super(props);

        const {
            formInspector,
            onChange,
            schemaOptions = {},
            value,
        } = this.props;
        const {
            exclude_duplicates: {
                value: excludeDuplicates = false,
            } = {},
        } = schemaOptions;

        if (typeof excludeDuplicates !== 'boolean') {
            throw new Error('The "exclude_duplicates" schemaOption must be a boolean if set!');
        }

        const {datasourceResourceKey} = smartContentConfigStore.getConfig(this.provider);

        if (value === undefined) {
            onChange(this.value, {isDefaultValue: true});
        }

        this.smartContentStore = new SmartContentStore(
            this.provider,
            this.value,
            formInspector.locale,
            datasourceResourceKey,
            formInspector.resourceKey === this.provider ? formInspector.id : undefined,
            schemaOptions,
            formInspector.metadataOptions?.webspace
        );

        smartContentStorePool.add(this.smartContentStore, excludeDuplicates);

        this.filterCriteriaChangeDisposer = reaction(
            () => toJS(this.smartContentStore.filterCriteria),
            (value): void => this.handleFilterCriteriaChange(value)
        );

        if (!excludeDuplicates || this.previousSmartContentStores.length === 0) {
            this.smartContentStore.start();
        } else {
            // If duplicates are excluded wait with loading the smart content until all previous ones have been loaded
            // Otherwise it is not known which ids to exclude for the initial request and has to be done a second time
            when(
                () => this.previousSmartContentStores.every((store) => !store.itemsLoading),
                (): void => {
                    smartContentStorePool.updateExcludedIds();
                    this.smartContentStore.start();
                }
            );
        }
    }

    componentWillUnmount() {
        smartContentStorePool.remove(this.smartContentStore);
        this.smartContentStore.destroy();
        this.filterCriteriaChangeDisposer();
    }

    handleFilterCriteriaChange = (filterCriteria: ?FilterCriteria) => {
        const {onChange, onFinish, value} = this.props;

        const currentValue = toJS(value);
        const newValue = toJS(filterCriteria);

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

        onChange(filterCriteria);
        onFinish();

        smartContentStorePool.updateExcludedIds();
    };

    handleItemClick = (itemId: string | number, item: Object) => {
        const {router} = this.props;

        const {resultToView, viewName} = this;

        if (!router || !viewName || !resultToView) {
            return;
        }

        router.navigate(
            viewName,
            Object.keys(resultToView).reduce((parameters, resultPath) => {
                parameters[resultToView[resultPath]] = jsonpointer.get(item, '/' + resultPath);
                return parameters;
            }, {})
        );
    };

    render() {
        const {
            disabled,
            label,
            schemaOptions: {
                category_root: {
                    value: categoryRootKey,
                } = {},
            } = {},
        } = this.props;

        if (categoryRootKey !== undefined && typeof categoryRootKey !== 'string') {
            throw new Error('The "category_root" schemaOption must a string if set!');
        }

        return (
            <SmartContentComponent
                categoryRootKey={categoryRootKey}
                defaultValue={this.defaultValue}
                disabled={!!disabled}
                fieldLabel={label}
                onItemClick={this.viewName && this.resultToView ? this.handleItemClick : undefined}
                presentations={this.presentations}
                store={this.smartContentStore}
            />
        );
    }
}

export default SmartContent;
