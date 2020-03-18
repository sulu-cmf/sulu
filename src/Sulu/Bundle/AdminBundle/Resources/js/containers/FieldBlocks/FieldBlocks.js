// @flow
import jsonpointer from 'json-pointer';
import React, {Fragment} from 'react';
import {toJS} from 'mobx';
import BlockCollection from '../../components/BlockCollection';
import type {BlockEntry} from '../../components/BlockCollection/types';
import type {BlockError, FieldTypeProps} from '../Form/types';
import blockPreviewTransformerRegistry from './registries/blockPreviewTransformerRegistry';
import FieldRenderer from './FieldRenderer';
import fieldBlocksStyles from './fieldBlocks.scss';

const MISSING_BLOCK_ERROR_MESSAGE = 'The "block" field type needs at least one type to be configured!';
const BLOCK_PREVIEW_TAG = 'sulu.block_preview';

export default class FieldBlocks extends React.Component<FieldTypeProps<Array<BlockEntry>>> {
    componentDidUpdate(prevProps: FieldTypeProps<Array<BlockEntry>>) {
        const {defaultType, types, value: value, onChange} = this.props;
        const {types: oldTypes} = prevProps;

        if (!types || !oldTypes) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        const newValue = toJS(value);
        let hasValueChanged = false;

        if (value && types !== oldTypes) {
            if (!defaultType) {
                throw new Error(
                    'It is impossible that a block has no defaultType. This should not happen and is likely a bug.'
                );
            }

            // set block to default type if type does not longer exist
            // this could happen for example in a template switch
            newValue.forEach((block, i) => {
                if (!types[block.type]) {
                    hasValueChanged = true;
                    newValue[i].type = defaultType;
                }
            });
        }

        // onChange should only be called when value was changed else it will end in a infinite loop
        if (hasValueChanged) {
            onChange(newValue);
        }
    }

    handleBlockChange = (index: number, name: string, value: Object) => {
        const {onChange, value: oldValues} = this.props;

        if (!oldValues) {
            return;
        }

        const newValues = toJS(oldValues);
        jsonpointer.set(newValues[index], '/' + name, value);

        onChange(newValues);
    };

    handleSortEnd = () => {
        const {onFinish} = this.props;
        onFinish();
    };

    getBlockSchemaType = (type: ?string) => {
        const {defaultType, types, schemaPath} = this.props;

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
                data={value}
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
            />
        );
    };

    // eslint-disable-next-line no-unused-vars
    renderCollapsedBlockContent = (value: Object, type: string, index: number) => {
        const blockSchemaType = this.getBlockSchemaType(type);
        const blockSchemaTypeForm = blockSchemaType.form;

        const previewPropertyNames = Object.keys(blockSchemaTypeForm)
            .filter((schemaKey) => {
                const schemaEntryTags = blockSchemaTypeForm[schemaKey].tags;
                return schemaEntryTags && schemaEntryTags.some((tag) => tag.name === BLOCK_PREVIEW_TAG);
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
                    if (blockSchemaTypeForm[propertyName].type === fieldTypeKey) {
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
                <div className={fieldBlocksStyles.type}>
                    {blockSchemaType.title}
                </div>
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

    render() {
        const {defaultType, disabled, maxOccurs, minOccurs, onChange, types, value} = this.props;

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
            <BlockCollection
                defaultType={defaultType}
                disabled={!!disabled}
                maxOccurs={maxOccurs}
                minOccurs={minOccurs}
                onChange={onChange}
                onSortEnd={this.handleSortEnd}
                renderBlockContent={this.renderBlockContent}
                types={blockTypes}
                value={value || []}
            />
        );
    }
}
