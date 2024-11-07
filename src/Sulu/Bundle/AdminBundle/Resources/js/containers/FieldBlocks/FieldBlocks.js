// @flow
import equals from 'fast-deep-equal';
import jsonpointer from 'json-pointer';
import jexl from 'jexl';
import React, {Fragment} from 'react';
import {action, computed, isObservableArray, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import BlockCollection from '../../components/BlockCollection';
import {translate} from '../../utils/Translator';
import {memoryFormStoreFactory} from '../Form';
import FormOverlay from '../FormOverlay';
import snackbarStore from '../../stores/snackbarStore';
import conditionDataProviderRegistry from '../Form/registries/conditionDataProviderRegistry';
import blockPreviewTransformerRegistry from './registries/blockPreviewTransformerRegistry';
import FieldRenderer from './FieldRenderer';
import type {BlockError, FieldTypeProps, FormStoreInterface} from '../Form/types';
import type {BlockEntry} from './types';
import type {Message} from '../../components/BlockCollection/types';

const MISSING_BLOCK_ERROR_MESSAGE = 'The "block" field type needs at least one type to be configured!';
const BLOCK_PREVIEW_TAG = 'sulu.block_preview';
const SETTINGS_KEY = 'settings';
const SETTINGS_PREFIX = '/settings/';
const SETTINGS_TAG = 'sulu.block_setting_icon';

@observer
class FieldBlocks extends React.Component<FieldTypeProps<Array<BlockEntry>>> {
    @observable openedBlockSettingsIndex: ?number;
    @observable blockSettingsFormStore: ?FormStoreInterface;
    @observable value: Object;
    oldIconValue: ?Object;
    computedIcons: Array<Array<string>> = [];

    constructor(props: FieldTypeProps<Array<BlockEntry>>) {
        super(props);

        this.setValue(this.props.value);
    }

    @action componentDidMount() {
        if (this.settingsFormKey) {
            // initialize empty blockSettingsFormStore because schema of the store is used for determining iconsMapping
            this.blockSettingsFormStore = memoryFormStoreFactory.createFromFormKey(
                this.settingsFormKey,
                {},
                this.props.formInspector.locale,
                undefined,
                this.props.formInspector.options
            );
        }
    }

    componentDidUpdate(prevProps: FieldTypeProps<Array<BlockEntry>>) {
        const {defaultType, onChange, types, value} = this.props;
        const {types: oldTypes} = prevProps;

        if (!equals(toJS(prevProps.value), toJS(value))){
            this.setValue(value);
        }

        if (!types || !oldTypes) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        let newValue = toJS(value);

        if (newValue && types !== oldTypes) {
            if (!defaultType) {
                throw new Error(
                    'It is impossible that a block has no defaultType. This should not happen and is likely a bug.'
                );
            }

            // set block to default type if type does not longer exist
            // this could happen for example in a template switch
            newValue = newValue.map((block) => {
                if (!types[block.type]) {
                    return {...block, type: defaultType};
                }

                return block;
            });
        }

        // onChange should only be called when value was changed else it will end in a infinite loop
        if (!equals(toJS(value), newValue)) {
            onChange(newValue);
        }
    }

    componentWillUnmount() {
        this.blockSettingsFormStore?.destroy();
    }

    @computed get settingsFormKey() {
        const {
            schemaOptions: {
                settings_form_key: {
                    value: settingsFormKey,
                } = {},
            },
        } = this.props;

        if (settingsFormKey !== undefined && typeof settingsFormKey !== 'string') {
            throw new Error('The "block" field types only accepts strings as "settings_form_key" schema option!');
        }

        return settingsFormKey;
    }

    @computed get addButtonText() {
        const {
            schemaOptions: {
                add_button_text: {
                    title: addButtonText,
                } = {},
            },
        } = this.props;

        if (addButtonText !== undefined && typeof addButtonText !== 'string') {
            throw new Error('The "block" field types only accepts strings as "add_button_text" schema option!');
        }

        return addButtonText;
    }

    @computed get pasteButtonText() {
        const {
            schemaOptions: {
                paste_button_text: {
                    title: pasteButtonText,
                } = {},
            },
        } = this.props;

        if (pasteButtonText !== undefined && typeof pasteButtonText !== 'string') {
            throw new Error('The "block" field types only accepts strings as "paste_button_text" schema option!');
        }

        return pasteButtonText;
    }

    @computed get collapsable() {
        const {
            schemaOptions: {
                collapsable: {
                    value: collapsable,
                } = {},
            },
        } = this.props;

        if (collapsable !== undefined && typeof collapsable !== 'boolean') {
            throw new Error('The "block" field types only accepts booleans as "collapsable" schema option!');
        }

        return collapsable;
    }

    @computed get movable() {
        const {
            schemaOptions: {
                movable: {
                    value: movable,
                } = {},
            },
        } = this.props;

        if (movable !== undefined && typeof movable !== 'boolean') {
            throw new Error('The "block" field types only accepts booleans as "collapsable" schema option!');
        }

        return movable;
    }

    @computed get iconsMapping() {
        const settingsSchema = this.blockSettingsFormStore?.schema;

        if (!settingsSchema) {
            return {};
        }

        const iconMappingReducerCreator = (prefixSchemaKey: string = '') => (iconsMapping, schemaKey) => {
            const pointer = '/' + prefixSchemaKey + schemaKey;

            if (!jsonpointer.has(settingsSchema, pointer)) {
                return iconsMapping;
            }

            const schemaEntry = jsonpointer.get(settingsSchema, pointer);

            if (schemaEntry.items) {
                return Object.keys(schemaEntry.items).reduce(
                    iconMappingReducerCreator(schemaKey + '/items/'),
                    iconsMapping
                );
            }

            const blockSettingsTag = schemaEntry.tags.find((tag) => tag.name === SETTINGS_TAG);

            if (blockSettingsTag) {
                iconsMapping[SETTINGS_PREFIX + schemaKey] = blockSettingsTag.attributes;
            }

            return iconsMapping;
        };

        return Object.keys(settingsSchema).reduce(iconMappingReducerCreator(), {});
    }

    @computed get precomputedConditions() {
        const precomputedConditions = [];
        for (const pointer in this.iconsMapping) {
            if (this.iconsMapping.hasOwnProperty(pointer)) {
                const {visibleCondition, icon} = this.iconsMapping[pointer];
                precomputedConditions.push({pointer, visibleCondition, icon});
            }
        }

        return precomputedConditions;
    }

    getDifference = (target: any, source: any): { [key: string]: any } => {
        if (target === source) {
            return {};
        }

        if (typeof target !== 'object' || target === null || typeof source !== 'object' || source === null) {
            return target;
        }

        if (Array.isArray(target) !== Array.isArray(source)) {
            return target;
        }

        const result: { [key: string]: any } = {};
        const keys = new Set(
            [
                ...(isObservableArray(target) ? target.keys() : Object.keys(target)),
                ...(isObservableArray(source) ? source.keys() : Object.keys(source)),
            ]
        );

        for (const key of keys) {
            const targetValue = target[key];
            const sourceValue = source[key];
            const diffValue = this.getDifference(targetValue, sourceValue);

            if (diffValue !== undefined && !equals(diffValue, {})) {
                result[key] = diffValue;
            }
        }

        return Object.keys(result).length > 0 ? result : {};
    };

    getChangedValues = (target: any, source: any): { [key: string]: any } => {
        // if target and source are not same data structure
        if (typeof target !== typeof source ||
            target === null || source === null ||
            Array.isArray(target) !== Array.isArray(source)
        ) {
            return target;
        }

        // get all unique keys from target and source
        // this is necessary because the keys of the target and source can be different
        const keys = new Set(
            [
                ...(isObservableArray(target) ? target.keys() : Object.keys(target)),
                ...(isObservableArray(source) ? source.keys() : Object.keys(source)),
            ]
        );

        // iterate over all keys and compare the values
        const result = {};
        for (const key of keys) {
            const targetValue = target[key];
            const sourceValue = source[key];

            // if the values are not equal, add the value to the result
            if (!equals(targetValue, sourceValue)) {
                result[key] = targetValue;
            }
        }

        return Object.keys(result).length > 0 ? result : {};
    }

    @computed get icons(): Array<Array<string>> {
        if (!this.value) {
            return [];
        }
        if (this.precomputedConditions.length === 0) {
            return [];
        }

        const jsValue = toJS(this.value);
        const changedValues = this.getChangedValues(jsValue, this.oldIconValue);
        this.oldIconValue = jsValue;

        for (const key in changedValues) {
            const value = this.value[key];

            const icons = [];
            for (const {pointer, visibleCondition, icon} of this.precomputedConditions) {
                const hasResult = jsonpointer.has(value, pointer);

                if (hasResult || visibleCondition !== undefined) {
                    if (visibleCondition !== undefined) {
                        const conditionData = this.getConditionData(value, pointer);

                        const jexlResult = jexl.evalSync(visibleCondition, conditionData);

                        if (jexlResult) {
                            icons.push(icon);
                        }
                    } else {
                        const getResult = jsonpointer.get(value, pointer);
                        if (getResult) {
                            icons.push(icon);
                        }
                    }
                }
            }

            this.computedIcons[parseInt(key)] = icons;
        }

        if (this.computedIcons.length !== this.value.length) {
            this.computedIcons = this.computedIcons.slice(0, this.value.length);
        }

        return this.computedIcons;
    }

    getConditionData(data: {[string]: any}, dataPath: ?string) {
        const {formInspector} = this.props;

        return conditionDataProviderRegistry.getAll().reduce(
            function(data, conditionDataProvider) {
                return {...data, ...conditionDataProvider(data, dataPath, formInspector)};
            },
            {...data}
        );
    }

    @action setValue = (value: Object) => {
        this.value = value;
    };

    handleBlockChange = (index: number, name: string, value: Object) => {
        const {onChange} = this.props;
        const oldValues = this.value;

        if (!oldValues) {
            return;
        }

        const newValues = toJS(oldValues);
        jsonpointer.set(newValues[index], '/' + name, value);

        this.setValue(newValues);

        onChange(newValues);
    };

    handleBlocksChange = (value: Object) => {
        const {onChange} = this.props;

        this.setValue(value);
        onChange(value);
    };

    handleSortEnd = () => {
        const {onFinish} = this.props;
        onFinish();
    };

    getBlockSchemaType = (type: ?string) => {
        const {defaultType, schemaPath, types} = this.props;

        if (!type) {
            throw new Error(
                'It is impossible that a block has no type. This should not happen and is likely a bug.'
            );
        }

        if (!types) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        if (types[type]) {
            return types[type];
        }

        if (!defaultType) {
            throw new Error(
                'It is impossible that a block has no defaultType. This should not happen and is likely a bug.'
            );
        }

        if (!types[defaultType]) {
            throw new Error(
                'The default type should exist in block "' + schemaPath + '".'
            );
        }

        return types[defaultType];
    };

    renderBlockContent = (value: Object, type: string, index: number, expanded: boolean) => {
        return expanded
            ? this.renderExpandedBlockContent(value, type, index)
            : this.renderCollapsedBlockContent(value, type, index);
    };

    renderExpandedBlockContent = (value: Object, type: string, index: number) => {
        const {
            data,
            dataPath,
            error,
            formInspector,
            onFinish,
            onSuccess,
            router,
            schemaPath,
            showAllErrors,
        } = this.props;

        const blockSchemaType = this.getBlockSchemaType(type);
        const errors = ((toJS(error): any): ?BlockError);

        return (
            <FieldRenderer
                data={data}
                dataPath={dataPath + '/' + index}
                errors={errors && errors.length > index && errors[index] ? errors[index] : undefined}
                formInspector={formInspector}
                index={index}
                onChange={this.handleBlockChange}
                onFieldFinish={onFinish}
                onSuccess={onSuccess}
                router={router}
                schema={blockSchemaType.form}
                schemaPath={schemaPath + '/types/' + type + '/form'}
                showAllErrors={showAllErrors}
                value={value}
            />
        );
    };

    // eslint-disable-next-line no-unused-vars
    renderCollapsedBlockContent = (value: Object, type: string, index: number) => {
        const blockSchemaType = this.getBlockSchemaType(type);
        const blockSchemaTypeForm = this.removeSections(blockSchemaType.form);

        const previewPropertyNames = Object.keys(blockSchemaTypeForm)
            .filter((schemaKey) => {
                const schemaEntryTags = blockSchemaTypeForm[schemaKey].tags;
                return schemaEntryTags &&
                    value[schemaKey] &&
                    schemaEntryTags.some((tag) => tag.name === BLOCK_PREVIEW_TAG);
            })
            .sort((propertyName1, propertyName2) => {
                const propertyTags1 = blockSchemaTypeForm[propertyName1].tags;
                const propertyTags2 = blockSchemaTypeForm[propertyName2].tags;

                if (!propertyTags1 || !propertyTags2) {
                    throw new Error(
                        'All properties without any tag should have been filtered before.'
                        + ' This should not happen and is likely a bug.'
                    );
                }

                const propertyTag1 = propertyTags1.find((tag) => tag.name === BLOCK_PREVIEW_TAG);
                const propertyTag2 = propertyTags2.find((tag) => tag.name === BLOCK_PREVIEW_TAG);

                if (!propertyTag1 || !propertyTag2) {
                    throw new Error(
                        'All properties not having the "sulu.block_preview" tag should have been filtered before.'
                        + ' This should not happen and is likely a bug.'
                    );
                }

                return (propertyTag2.priority || 0) - (propertyTag1.priority || 0);
            });

        if (previewPropertyNames.length === 0) {
            for (const fieldTypeKey of blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority) {
                for (const propertyName of Object.keys(blockSchemaTypeForm)) {
                    if (blockSchemaTypeForm[propertyName].type === fieldTypeKey && value[propertyName]) {
                        previewPropertyNames.push(propertyName);
                        break;
                    }
                }

                if (previewPropertyNames.length >= 3) {
                    break;
                }
            }
        }

        return (
            <Fragment>
                {previewPropertyNames.map((previewPropertyName) =>
                    blockPreviewTransformerRegistry.has(blockSchemaTypeForm[previewPropertyName].type)
                    && value[previewPropertyName]
                    && (
                        <Fragment key={previewPropertyName}>
                            {blockPreviewTransformerRegistry
                                .get(blockSchemaTypeForm[previewPropertyName].type)
                                .transform(value[previewPropertyName], blockSchemaTypeForm[previewPropertyName])
                            }
                        </Fragment>
                    )
                )}
            </Fragment>
        );
    };

    @action handleSettingsClick = (index: number) => {
        const settingsFormKey = this.settingsFormKey;

        if (!settingsFormKey || !this.value) {
            return;
        }

        // create new formstore to make sure that overlay displays correct data
        this.blockSettingsFormStore?.destroy();
        this.blockSettingsFormStore = memoryFormStoreFactory.createFromFormKey(
            settingsFormKey,
            {...this.value[index][SETTINGS_KEY]},
            this.props.formInspector.locale,
            undefined,
            this.props.formInspector.options
        );

        this.openedBlockSettingsIndex = index;
    };

    handleSettingsOverlayClose = () => {
        this.closeSettingsOverlay();
    };

    handleSettingsOverlayConfirm = () => {
        this.applySettingsFromOverlay();
        this.closeSettingsOverlay();
    };

    handleDisplaySnackbar = (message: Message) => {
        snackbarStore.add(message, 2500);
    };

    @action closeSettingsOverlay = () => {
        this.openedBlockSettingsIndex = undefined;
    };

    applySettingsFromOverlay = () => {
        const {onChange} = this.props;
        const oldValues = this.value || [];

        const {blockSettingsFormStore, openedBlockSettingsIndex} = this;

        if (!blockSettingsFormStore
            || openedBlockSettingsIndex === undefined
            || openedBlockSettingsIndex === null
            || !oldValues)
        {
            return;
        }

        const newValue = [
            ...oldValues.slice(0, openedBlockSettingsIndex),
            {...oldValues[openedBlockSettingsIndex], [SETTINGS_KEY]: blockSettingsFormStore.data},
            ...oldValues.slice(openedBlockSettingsIndex + 1),
        ];

        this.setValue(newValue);
        onChange(newValue);
    };

    removeSections(blockSchemaTypeForm: Object) {
        let filteredForm = {};
        Object.keys(blockSchemaTypeForm).forEach((key) => {
            if (blockSchemaTypeForm[key]['type'] === 'section') {
                filteredForm = {...filteredForm, ...this.removeSections(blockSchemaTypeForm[key]['items'])};
                return false;
            }

            filteredForm[key] = blockSchemaTypeForm[key];
        });

        return filteredForm;
    }

    render() {
        const {defaultType, disabled, maxOccurs, minOccurs, types} = this.props;
        const value = this.value || [];
        const blockSettingsFormStore = this.blockSettingsFormStore;

        if (!defaultType) {
            throw new Error('The "block" field type needs a defaultType!');
        }

        if (!types) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        const blockTypes = Object.keys(types).reduce((blockTypes, current) => {
            blockTypes[current] = types[current].title;
            return blockTypes;
        }, {});

        return (
            <>
                <BlockCollection
                    addButtonText={this.addButtonText}
                    collapsable={this.collapsable}
                    defaultType={defaultType}
                    disabled={!!disabled}
                    icons={this.icons}
                    maxOccurs={maxOccurs}
                    minOccurs={minOccurs}
                    movable={this.movable}
                    onChange={this.handleBlocksChange}
                    onDisplaySnackbar={this.handleDisplaySnackbar}
                    onSettingsClick={this.settingsFormKey ? this.handleSettingsClick : undefined}
                    onSortEnd={this.handleSortEnd}
                    pasteButtonText={this.pasteButtonText}
                    renderBlockContent={this.renderBlockContent}
                    types={blockTypes}
                    value={value}
                />
                {this.openedBlockSettingsIndex !== undefined && blockSettingsFormStore && (
                    <FormOverlay
                        confirmDisabled={!blockSettingsFormStore.dirty}
                        confirmText={translate('sulu_admin.apply')}
                        formStore={blockSettingsFormStore}
                        onClose={this.handleSettingsOverlayClose}
                        onConfirm={this.handleSettingsOverlayConfirm}
                        open={!!blockSettingsFormStore}
                        size="small"
                        title={translate('sulu_admin.block_settings')}
                    />
                )}
            </>
        );
    }
}

export default FieldBlocks;
