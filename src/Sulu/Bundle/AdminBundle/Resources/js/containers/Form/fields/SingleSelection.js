// @flow
import React from 'react';
import {computed, isArrayLike, observable, reaction, toJS} from 'mobx';
import log from 'loglevel';
import jsonpointer from 'json-pointer';
import equals from 'fast-deep-equal';
import {observer} from 'mobx-react';
import ResourceSingleSelect from '../../../containers/ResourceSingleSelect';
import SingleAutoComplete from '../../../containers/SingleAutoComplete';
import SingleSelectionContainer from '../../../containers/SingleSelection';
import userStore from '../../../stores/userStore';
import {translate} from '../../../utils/Translator';
import FormInspector from '../FormInspector';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import type {SchemaOption} from '../types';
import type {FieldTypeProps} from '../../../types';
import type {IObservableArray, IObservableValue} from 'mobx/lib/mobx';

type Value = ?(string | number);
type Props = FieldTypeProps<Value>;

@observer
class SingleSelection extends React.Component<Props>
{
    @observable requestOptions: {[string]: mixed};

    autoCompleteSelectionStore: ?SingleSelectionStore<string | number>;
    changeAutoCompleteSelectionDisposer: ?() => *;

    constructor(props: Props) {
        super(props);

        if (this.type !== 'list_overlay' && this.type !== 'single_select' && this.type !== 'auto_complete') {
            throw new Error(
                'The Selection field must either be declared as "list_overlay", "single_select" '
                + 'or as "auto_complete", received type was "' + this.type + '"!'
            );
        }

        const {
            fieldTypeOptions: {
                resource_key: resourceKey,
            },
            formInspector,
            schemaOptions: {
                request_parameters: {
                    value: unvalidatedRequestParameters = [],
                } = {},
                resource_store_properties_to_request: {
                    value: unvalidatedResourceStorePropertiesToRequest = [],
                } = {},
                form_options_to_request: {
                    value: unvalidatedFormOptionsToRequest = [],
                } = {},
            },
        } = this.props;

        if (!resourceKey) {
            throw new Error('The selection field needs a "resource_key" option to work properly');
        }

        if (!isArrayLike(unvalidatedRequestParameters)) {
            throw new Error('The "request_parameters" schemaOption must be an array!');
        }
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        const requestParameters: Array<any> | IObservableArray<any> = unvalidatedRequestParameters;

        if (!isArrayLike(unvalidatedResourceStorePropertiesToRequest)) {
            throw new Error('The "resource_store_properties_to_request" schemaOption must be an array!');
        }
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        const resourceStorePropertiesToRequest: Array | IObservableArray = unvalidatedResourceStorePropertiesToRequest;

        if (!isArrayLike(unvalidatedFormOptionsToRequest)) {
            throw new Error('The "form_options_to_request" schemaOption must be an array!');
        }
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        const formOptionsToRequest: Array | IObservableArray = unvalidatedFormOptionsToRequest;

        this.requestOptions = this.buildRequestOptions(
            requestParameters,
            resourceStorePropertiesToRequest,
            formOptionsToRequest,
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
                    formOptionsToRequest,
                    formInspector
                );

                if (!equals(this.requestOptions, newRequestOptions)) {
                    this.requestOptions = newRequestOptions;
                }
            }
        });

        if (this.type === 'auto_complete') {
            this.autoCompleteSelectionStore = new SingleSelectionStore(
                resourceKey,
                this.value,
                this.locale
            );

            this.changeAutoCompleteSelectionDisposer = reaction(
                () => this.autoCompleteSelectionStore?.item,
                this.handleAutoCompleteSelectionChange
            );
        }
    }

    componentWillUnmount() {
        if (this.changeAutoCompleteSelectionDisposer) {
            this.changeAutoCompleteSelectionDisposer();
        }
    }

    buildRequestOptions(
        requestParameters: Array<SchemaOption>,
        resourceStorePropertiesToRequest: Array<SchemaOption>,
        formOptionsToRequest: Array<SchemaOption>,
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

        formOptionsToRequest.forEach((optionToRequest) => {
            const {name: parameterName, value: optionName} = optionToRequest;
            const optionKey = typeof optionName === 'string' ? optionName : parameterName;
            if (formInspector.options && optionKey in formInspector.options) {
                requestOptions[parameterName] = toJS(formInspector.options[optionKey]);
            }
        });

        return requestOptions;
    }

    handleAutoCompleteSelectionChange = (selectedItem: ?{id: Value}) => {
        if (!this.autoCompleteSelectionStore) {
            throw new Error(
                'The SelectionStore has not been initialized! This should not happen and is likely a bug.'
            );
        }

        if (this.autoCompleteSelectionStore.loading) {
            return;
        }

        if (this.value !== selectedItem?.id) {
            if (this.useDeprecatedObjectDataFormat) {
                this.handleChange((selectedItem: any));
            } else {
                this.handleChange(selectedItem?.id);
            }
        }
    };

    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    @computed get value(): Value {
        const {value, dataPath} = this.props;

        if (value && typeof value === 'object') {
            if (this.type === 'auto_complete' && this.useDeprecatedObjectDataFormat) {
                return value.id;
            }

            log.warn(
                'The "SingleSelection" field with the path "' + dataPath + '" expects an id as value but '
                + 'received an object instead. Is it possible that your API returns a serialized object?'
                + '\n\nThe Sulu form view expects that your API returns the data in the same format as it is sent '
                + 'to the server when submitting the form. '
                + '\nSulu will try to extract the id from the given object heuristically. '
                + 'This decreases performance and might lead to errors or other unexpected behaviour.'
            );

            return value.id;
        }

        return value;
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

    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    @computed get viewName() {
        const {
            fieldTypeOptions: {
                view: {
                    name,
                } = {},
            },
        } = this.props;

        return name;
    }

    @computed get resultToView() {
        const {
            fieldTypeOptions: {
                view: {
                    result_to_view: resultToView,
                } = {},
            },
        } = this.props;

        return resultToView;
    }

    @computed get useDeprecatedObjectDataFormat() {
        const {
            schemaOptions: {
                use_deprecated_object_data_format: {
                    value: useDeprecatedObjectDataFormat = false,
                } = {},
            } = {},
        } = this.props;

        if (useDeprecatedObjectDataFormat) {
            // @deprecated
            log.warn(
                'The "use_deprecated_object_data_format" param is deprecated since version 2.3 and will be removed. ' +
                'You should adjust your API to process an id instead of a serialized object.'
            );
        }

        return useDeprecatedObjectDataFormat;
    }

    handleItemClick = (itemId: Value, item: ?Object) => {
        const {router} = this.props;

        const {resultToView, viewName} = this;

        if (!router) {
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
        if (this.type === 'list_overlay') {
            return this.renderListOverlay();
        }

        if (this.type === 'single_select') {
            return this.renderSingleSelect();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }

        throw new Error('The "' + this.type + '" type does not exist in the SingleSelection field type.');
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
                        detail_options: typeDetailOptions,
                        list_key: listKey,
                        display_properties: displayProperties,
                        empty_text: emptyText,
                        icon,
                        overlay_title: overlayTitle,
                    },
                },
            },
            schemaOptions: {
                form_options_to_list_options: {
                    value: unvalidatedFormOptionsToListOptions = [],
                } = {},
                item_disabled_condition: {
                    value: itemDisabledCondition,
                } = {},
                allow_deselect_for_disabled_items: {
                    value: allowDeselectForDisabledItems = true,
                } = {},
                types: {
                    value: types,
                } = {},
            } = {},
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

        if (!isArrayLike(unvalidatedFormOptionsToListOptions)) {
            throw new Error('The "form_options_to_list_options" option has to be an array if defined!');
        }
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        const formOptionsToListOptions: Array<any> | IObservableArray<any> = unvalidatedFormOptionsToListOptions;

        if (typeDetailOptions && typeof typeDetailOptions !== 'object') {
            throw new Error('The "detail_options" option has to be an array if defined!');
        }

        const formListOptions = formOptionsToListOptions.reduce((currentOptions, formOption) => {
            if (!formOption.name) {
                throw new Error('All options set in "form_options_to_list_options" must define name!');
            }
            currentOptions[formOption.name] = formInspector.options[formOption.name];

            return currentOptions;
        }, {});

        const typeOptions = types ? {types} : undefined;

        const listOptions = {
            ...this.requestOptions,
            ...formListOptions,
            ...typeOptions,
        };

        const detailOptions = {
            ...this.requestOptions,
            ...typeDetailOptions,
        };

        return (
            <SingleSelectionContainer
                adapter={adapter}
                allowDeselectForDisabledItems={!!allowDeselectForDisabledItems}
                detailOptions={detailOptions}
                disabled={!!disabled}
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                displayProperties={displayProperties}
                emptyText={translate(emptyText)}
                icon={icon}
                itemDisabledCondition={itemDisabledCondition}
                listKey={listKey || resourceKey}
                listOptions={listOptions}
                locale={this.locale}
                onChange={this.handleChange}
                onItemClick={this.viewName && this.resultToView && this.handleItemClick}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={this.value}
            />
        );
    }

    renderSingleSelect() {
        const {
            disabled,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    single_select: {
                        display_property: displayProperty,
                        id_property: idProperty,
                        overlay_title: overlayTitle,
                    } = {},
                },
            },
            schemaOptions: {
                editable: {
                    value: editable,
                } = {},
            } = {},
        } = this.props;

        if (typeof displayProperty !== 'string') {
            throw new Error('The "display_property" field-type option must be a string!');
        }

        if (typeof idProperty !== 'string') {
            throw new Error('The "id_property" field-type option must be a string!');
        }

        return (
            <ResourceSingleSelect
                disabled={!!disabled}
                displayProperty={displayProperty}
                editable={!!editable}
                idProperty={idProperty}
                onChange={this.handleChange}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={this.value}
            />
        );
    }

    renderAutoComplete() {
        const {
            disabled,
            dataPath,
            fieldTypeOptions,
            formInspector,
            schemaOptions: {
                data_path_to_auto_complete: {
                    value: unvalidatedDataPathToAutoComplete = [],
                } = {},
            },
        } = this.props;

        if (!fieldTypeOptions.types.auto_complete) {
            throw new Error(
                'The single_selection field needs an "auto_complete" type if rendered as SingleAutoComplete'
            );
        }

        const {
            types: {
                auto_complete: {
                    display_property: displayProperty,
                    search_properties: searchProperties,
                },
            },
        } = fieldTypeOptions;

        if (!isArrayLike(unvalidatedDataPathToAutoComplete)) {
            throw new Error('The "data_path_to_auto_complete" schemaOption must be an array!');
        }
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        const dataPathToAutoComplete: Array<any> | IObservableArray<any> = unvalidatedDataPathToAutoComplete;

        if (dataPathToAutoComplete.length > 0 ){
            // @deprecated
            log.warn(
                'The "data_path_to_auto_complete" option is deprecated since version 2.2 and will be removed. ' +
                'Use the "resource_store_properties_to_request" option instead.'
            );
        }

        const options = {
            ...dataPathToAutoComplete.reduce((options, schemaEntry) => {
                const {name, value} = schemaEntry;
                if (typeof name !== 'string' || typeof value !== 'string') {
                    throw new Error(
                        'An entry of the "data_path_to_auto_complete" schemaOption must provide strings for their ' +
                        'name and value'
                    );
                }

                options[value] = formInspector.getValueByPath('/' + name);

                return options;
            }, {}),
            ...this.requestOptions,
        };

        if (!this.autoCompleteSelectionStore) {
            throw new Error('The SelectionStore has not been initialized! This should not happen and is likely a bug.');
        }

        return (
            <SingleAutoComplete
                disabled={!!disabled}
                displayProperty={displayProperty}
                id={dataPath}
                options={options}
                searchProperties={searchProperties}
                selectionStore={this.autoCompleteSelectionStore}
            />
        );
    }
}

export default SingleSelection;
