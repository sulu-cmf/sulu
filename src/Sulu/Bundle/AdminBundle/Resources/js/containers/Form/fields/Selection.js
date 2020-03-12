// @flow
import React from 'react';
import {computed, observable, intercept, toJS, reaction} from 'mobx';
import type {IObservableValue} from 'mobx';
import equal from 'fast-deep-equal';
import {observer} from 'mobx-react';
import FormInspector from '../FormInspector';
import List from '../../../containers/List';
import ListStore from '../../../containers/List/stores/ListStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import MultiAutoComplete from '../../../containers/MultiAutoComplete';
import {translate} from '../../../utils/Translator';
import MultiSelectionComponent from '../../MultiSelection';
import userStore from '../../../stores/userStore';
import type {FieldTypeProps} from '../../../types';
import type {SchemaOption} from '../types';
import selectionStyles from './selection.scss';

type Props = FieldTypeProps<Array<string | number>>;

const USER_SETTINGS_KEY = 'selection';

@observer
class Selection extends React.Component<Props> {
    listStore: ?ListStore;
    autoCompleteSelectionStore: ?MultiSelectionStore<string | number>;
    changeListDisposer: ?() => *;
    changeAutoCompleteSelectionDisposer: ?() => *;
    changeListOptionsDisposer: ?() => *;
    changeLocaleDisposer: ?() => *;

    @observable requestOptions: {[string]: mixed};

    constructor(props: Props) {
        super(props);

        if (this.type !== 'list_overlay' && this.type !== 'list' && this.type !== 'auto_complete') {
            throw new Error(
                'The Selection field must either be declared as "overlay", "list" or as "auto_complete", '
                + 'received type was "' + this.type + '"!'
            );
        }

        const {
            fieldTypeOptions: {
                resource_key: resourceKey,
            },
            formInspector,
            schemaOptions: {
                request_parameters: {
                    value: requestParameters = [],
                } = {},
                resource_store_properties_to_request: {
                    value: resourceStorePropertiesToRequest = [],
                } = {},
            },
        } = this.props;

        if (!resourceKey) {
            throw new Error('The selection field needs a "resource_key" option to work properly');
        }

        if (!Array.isArray(requestParameters)) {
            throw new Error('The "request_parameters" schemaOption must be an array!');
        }

        if (!Array.isArray(resourceStorePropertiesToRequest)) {
            throw new Error('The "resource_store_properties_to_request" schemaOption must be an array!');
        }

        this.requestOptions = this.buildRequestOptions(
            requestParameters,
            resourceStorePropertiesToRequest,
            formInspector
        );

        // update requestOptions observable if one of the "resource_store_properties_to_request" properties is changed
        formInspector.addFinishFieldHandler((dataPath) => {
            const observedDataPaths = resourceStorePropertiesToRequest.map((property) => {
                return typeof property.value === 'string' ? '/' + property.value : '/' + property.name;
            });

            if (observedDataPaths.includes(dataPath)) {
                const newRequestOptions = this.buildRequestOptions(
                    requestParameters,
                    resourceStorePropertiesToRequest,
                    formInspector
                );

                if (!equal(this.requestOptions, newRequestOptions)) {
                    this.requestOptions = newRequestOptions;
                }
            }
        });

        if (this.type === 'list') {
            const {
                fieldTypeOptions: {
                    types: {
                        list: {
                            list_key: listKey,
                        },
                    },
                },
                value,
            } = this.props;

            this.listStore = new ListStore(
                resourceKey,
                listKey || resourceKey,
                USER_SETTINGS_KEY,
                {locale: this.locale, page: observable.box()},
                this.requestOptions,
                undefined,
                value
            );

            this.changeListDisposer = reaction(
                () => (this.listStore ? this.listStore.selectionIds : []),
                this.handleListSelectionChange
            );

            this.changeListOptionsDisposer = reaction(
                () => this.requestOptions,
                (requestOptions) => {
                    const listStore = this.listStore;
                    if (!listStore) {
                        throw new Error('The ListStore has not been initialized! This is likely a bug.');
                    }

                    // reset liststore to reload whole tree instead of children of current active item
                    listStore.reset();
                    // set selected items as initialSelectionIds to expand them in case of a tree
                    listStore.initialSelectionIds = listStore.selectionIds;
                    listStore.options = {...listStore.options, ...requestOptions};
                }
            );

            this.changeLocaleDisposer = intercept(this.locale, '', (change) => {
                if (this.listStore) {
                    this.listStore.sendRequestDisposer();
                }

                return change;
            });
        } else if (this.type === 'auto_complete') {
            const {value} = this.props;

            this.autoCompleteSelectionStore = new MultiSelectionStore(
                resourceKey,
                value || [],
                this.locale,
                this.autoCompleteFilterParameter
            );

            this.changeAutoCompleteSelectionDisposer = reaction(
                () => this.autoCompleteSelectionStore
                    ? this.autoCompleteSelectionStore.items.map((item) => item[this.autoCompleteIdProperty])
                    : [],
                this.handleAutoCompleteSelectionChange
            );
        }
    }

    componentWillUnmount() {
        if (this.changeListDisposer) {
            this.changeListDisposer();
        }

        if (this.changeAutoCompleteSelectionDisposer) {
            this.changeAutoCompleteSelectionDisposer();
        }

        if (this.changeListOptionsDisposer) {
            this.changeListOptionsDisposer();
        }

        if (this.changeLocaleDisposer) {
            this.changeLocaleDisposer();
        }

        if (this.listStore) {
            this.listStore.destroy();
        }
    }

    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    @computed get type() {
        const defaultType = this.props.fieldTypeOptions.default_type;
        if (typeof defaultType !== 'string') {
            throw new Error('The "default_type" field-type option must be a string!');
        }

        const {schemaOptions} = this.props;

        const {
            type: {
                value: type = defaultType,
            } = {},
        } = schemaOptions;

        if (typeof type !== 'string') {
            throw new Error('The "type" schema option must be a string!');
        }

        return type;
    }

    @computed get autoCompleteIdProperty() {
        const {
            fieldTypeOptions: {
                types: {
                    auto_complete: {
                        id_property: idProperty,
                    },
                },
            },
        } = this.props;

        return idProperty;
    }

    @computed get autoCompleteFilterParameter() {
        const {
            fieldTypeOptions: {
                types: {
                    auto_complete: {
                        filter_parameter: filterParameter,
                    },
                },
            },
        } = this.props;

        return filterParameter;
    }

    buildRequestOptions(
        requestParameters: Array<SchemaOption>,
        resourceStorePropertiesToRequest: Array<SchemaOption>,
        formInspector: FormInspector
    ) {
        const requestOptions = {};

        requestParameters.forEach((parameter) => {
            requestOptions[parameter.name] = parameter.value;
        });

        resourceStorePropertiesToRequest.forEach((propertyToRequest) => {
            const {name: parameterName, value: propertyName} = propertyToRequest;
            const propertyPath = typeof propertyName === 'string' ? propertyName : parameterName;
            requestOptions[parameterName] = toJS(formInspector.getValueByPath('/' + propertyPath));
        });

        return requestOptions;
    }

    render() {
        if (this.type === 'list_overlay') {
            return this.renderListOverlay();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }

        if (this.type === 'list') {
            return this.renderList();
        }

        throw new Error('The "' + this.type + '" type does not exist in the Selection field type.');
    }

    renderListOverlay() {
        const {
            disabled,
            formInspector,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    list_overlay: {
                        adapter,
                        list_key: listKey,
                        display_properties: displayProperties,
                        icon,
                        label,
                        overlay_title: overlayTitle,
                    },
                },
            },
            schemaOptions: {
                types: {
                    value: types,
                } = {},
                item_disabled_condition: {
                    value: itemDisabledCondition,
                } = {},
                allow_deselect_for_disabled_items: {
                    value: allowDeselectForDisabledItems = true,
                } = {},
            },
            value,
        } = this.props;

        if (types !== undefined && typeof types !== 'string') {
            throw new Error('The "types" schema option must be a string if given!');
        }

        if (itemDisabledCondition !== undefined && typeof itemDisabledCondition !== 'string') {
            throw new Error('The "item_disabled_condition" schema option must be a string if given!');
        }

        if (allowDeselectForDisabledItems !== undefined && typeof allowDeselectForDisabledItems !== 'boolean') {
            throw new Error('The "allow_deselect_for_disabled_items" schema option must be a boolean if given!');
        }

        if (!adapter) {
            throw new Error('The selection field needs a "adapter" option to work properly');
        }

        const options = {...this.requestOptions};
        if (types) {
            options.types = types;
        }

        return (
            <MultiSelectionComponent
                adapter={adapter}
                allowDeselectForDisabledItems={!!allowDeselectForDisabledItems}
                disabled={!!disabled}
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                displayProperties={displayProperties}
                icon={icon}
                itemDisabledCondition={itemDisabledCondition}
                label={translate(label, {count: value ? value.length : 0})}
                listKey={listKey || resourceKey}
                locale={this.locale}
                onChange={this.handleMultiSelectionChange}
                options={options}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={value || []}
            />
        );
    }

    handleMultiSelectionChange = (selectedIds: Array<string | number>) => {
        const {onChange, onFinish} = this.props;

        onChange(selectedIds);
        onFinish();
    };

    renderAutoComplete() {
        if (!this.autoCompleteSelectionStore) {
            throw new Error('The SelectionStore has not been initialized! This should not happen and is likely a bug.');
        }

        const {
            dataPath,
            disabled,
            fieldTypeOptions: {
                types: {
                    auto_complete: {
                        allow_add: allowAdd,
                        display_property: displayProperty,
                        search_properties: searchProperties,
                    },
                },
            },
        } = this.props;

        if (!displayProperty) {
            throw new Error('The selection field needs a "display_property" option to work properly!');
        }

        if (!searchProperties) {
            throw new Error('The selection field needs a "search_properties" option to work properly!');
        }

        return (
            <MultiAutoComplete
                allowAdd={allowAdd}
                disabled={!!disabled}
                displayProperty={displayProperty}
                id={dataPath}
                idProperty={this.autoCompleteIdProperty}
                searchProperties={searchProperties}
                selectionStore={this.autoCompleteSelectionStore}
            />
        );
    }

    renderList() {
        if (!this.listStore) {
            throw new Error('The ListStore has not been initialized! This should not happen and is likely a bug.');
        }

        const {
            disabled,
            fieldTypeOptions: {
                types: {
                    list: {
                        adapter,
                    },
                },
            },
            schemaOptions: {
                item_disabled_condition: {
                    value: itemDisabledCondition,
                } = {},
            },
        } = this.props;

        if (!adapter) {
            throw new Error('The selection field needs a "adapter" option for the list type to work properly');
        }

        if (itemDisabledCondition !== undefined && typeof itemDisabledCondition !== 'string') {
            throw new Error('The "item_disabled_condition" schema option must be a string if given!');
        }

        return (
            <div className={selectionStyles.list}>
                <List
                    adapters={[adapter]}
                    disabled={!!disabled}
                    itemDisabledCondition={itemDisabledCondition}
                    searchable={false}
                    store={this.listStore}
                />
            </div>
        );
    }

    handleListSelectionChange = (selectedIds: Array<string | number>) => {
        const {onChange, onFinish, value} = this.props;

        if (!this.listStore) {
            throw new Error(
                'The ListStore has not been initialized! This should not happen and is likely a bug.'
            );
        }

        if (this.listStore.dataLoading || this.listStore.loading) {
            return;
        }

        if (!equal(toJS(value), toJS(selectedIds))) {
            onChange(selectedIds);
            onFinish();
        }
    };

    handleAutoCompleteSelectionChange = (selectedIds: Array<string | number>) => {
        const {onChange, onFinish, value} = this.props;

        if (!this.autoCompleteSelectionStore) {
            throw new Error(
                'The SelectionStore has not been initialized! This should not happen and is likely a bug.'
            );
        }

        if (this.autoCompleteSelectionStore.loading) {
            return;
        }

        if (!equal(toJS(value), toJS(selectedIds))) {
            onChange(selectedIds);
            onFinish();
        }
    };
}

export default Selection;
