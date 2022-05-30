// @flow
import React from 'react';
import {action, observable, toJS, reaction, computed} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {arrayMove, translate} from '../../utils';
import {clipboardStore} from '../../stores';
import Button from '../Button';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';
import type {RenderBlockContentCallback} from './types';

type Props<T: string, U: {type: T}> = {|
    addButtonText?: ?string,
    collapsable: boolean,
    defaultType: T,
    disabled: boolean,
    icons?: Array<Array<string>>,
    maxOccurs?: ?number,
    minOccurs?: ?number,
    movable: boolean,
    onChange: (value: Array<U>) => void,
    onSettingsClick?: (index: number) => void,
    onSortEnd?: (oldIndex: number, newIndex: number) => void,
    pasteButtonText?: ?string,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    types?: {[key: T]: string},
    value: Array<U>,
|};

const BLOCKS_CLIPBOARD_KEY = 'blocks';

@observer
class BlockCollection<T: string, U: {type: T}> extends React.Component<Props<T, U>> {
    static idCounter = 0;

    static defaultProps = {
        collapsable: true,
        disabled: false,
        movable: true,
        value: [],
    };

    @observable pasteableBlocks: Array<number> = [];
    @observable generatedBlockIds: Array<number> = [];
    @observable expandedBlocks: Array<boolean> = [];

    fillArraysDisposer: ?() => *;
    setPasteableBlocksDisposer: ?() => *;

    constructor(props: Props<T, U>) {
        super(props);

        this.fillArraysDisposer = reaction(() => this.props.value.length, this.fillArrays, {fireImmediately: true});
        this.setPasteableBlocksDisposer = clipboardStore.observe(BLOCKS_CLIPBOARD_KEY, action((blocks) => {
            this.pasteableBlocks = (blocks: any) || [];
        }));

        this.pasteableBlocks = (clipboardStore.get(BLOCKS_CLIPBOARD_KEY): any) || [];
    }

    componentWillUnmount() {
        this.fillArraysDisposer?.();
        this.setPasteableBlocksDisposer?.();
    }

    fillArrays = () => {
        const {collapsable, defaultType, onChange, minOccurs, value} = this.props;
        const {expandedBlocks, generatedBlockIds} = this;

        if (!value) {
            return;
        }

        if (expandedBlocks.length > value.length) {
            expandedBlocks.splice(value.length);
        }

        if (generatedBlockIds.length > value.length) {
            generatedBlockIds.splice(value.length);
        }

        const collapsed = collapsable ? false : true;

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(collapsed));
        generatedBlockIds.push(
            ...new Array(value.length - generatedBlockIds.length).fill(false).map(() => ++BlockCollection.idCounter)
        );
        if (minOccurs && value.length < minOccurs) {
            expandedBlocks.push(...new Array(minOccurs - value.length).fill(true));
            generatedBlockIds.push(
                ...new Array(minOccurs - value.length).fill(false).map(() => ++BlockCollection.idCounter)
            );

            onChange([
                ...value,
                ...Array.from(
                    {length: minOccurs - value.length},
                    // $FlowFixMe
                    () => ({type: defaultType})
                ),
            ]);
        }
    };

    @action handleAddBlock = (insertionIndex: number) => {
        const {defaultType, onChange, value} = this.props;

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(insertionIndex, 0, true);
            this.generatedBlockIds.splice(insertionIndex, 0, ++BlockCollection.idCounter);

            const elementsBefore = value.slice(0, insertionIndex);
            const elementsAfter = value.slice(insertionIndex);
            // $FlowFixMe
            onChange([...elementsBefore, {type: defaultType}, ...elementsAfter]);
        }
    };

    @action handlePasteBlocks = (insertionIndex: number) => {
        const {onChange, value} = this.props;

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            // TODO: gracefully handle clipboard data this is incompatible with available block types

            this.expandedBlocks.splice(
                insertionIndex, 0, ...this.pasteableBlocks.map(() => true)
            );
            this.generatedBlockIds.splice(
                insertionIndex, 0, ...this.pasteableBlocks.map(() => ++BlockCollection.idCounter)
            );

            const elementsBefore = value.slice(0, insertionIndex);
            const elementsAfter = value.slice(insertionIndex);
            // $FlowFixMe
            onChange([...elementsBefore, ...this.pasteableBlocks, ...elementsAfter]);
            clipboardStore.set(BLOCKS_CLIPBOARD_KEY, undefined);
        }
    };

    @action handleRemoveBlock = (index: number) => {
        const {onChange, value} = this.props;

        if (this.hasMinimumReached) {
            throw new Error('The minimum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(index, 1);
            this.generatedBlockIds.splice(index, 1);
            onChange(value.filter((element, arrayIndex) => arrayIndex != index));
        }
    };

    @action handleDuplicateBlock = (index: number) => {
        const {onChange, value} = this.props;

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(index, 0, true);
            this.generatedBlockIds.splice(index, 0, ++BlockCollection.idCounter);

            const elementsBefore = value.slice(0, index);
            const elementsAfter = value.slice(index);
            // $FlowFixMe
            onChange([...elementsBefore, {...toJS(value[index])}, ...elementsAfter]);
        }
    };

    handleCopyBlock = (index: number) => {
        const {value} = this.props;

        if (value) {
            const block = {...toJS(value[index])};
            clipboardStore.set(BLOCKS_CLIPBOARD_KEY, [block]);
        }
    };

    handleCutBlock = (index: number) => {
        this.handleCopyBlock(index);
        this.handleRemoveBlock(index);
    };

    @action handleSortEnd = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
        const {onChange, onSortEnd, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
        this.generatedBlockIds = arrayMove(this.generatedBlockIds, oldIndex, newIndex);
        onChange(arrayMove(value, oldIndex, newIndex));

        if (onSortEnd) {
            onSortEnd(oldIndex, newIndex);
        }
    };

    @action handleCollapse = (index: number) => {
        this.expandedBlocks[index] = false;
    };

    @action handleExpand = (index: number) => {
        this.expandedBlocks[index] = true;
    };

    handleSettingsClick = (index: number) => {
        const {onSettingsClick} = this.props;

        if (onSettingsClick) {
            onSettingsClick(index);
        }
    };

    @action handleTypeChange: (type: T, index: number) => void = (type, index) => {
        const {onChange, value} = this.props;
        const newValue = toJS(value);
        newValue[index].type = type;
        onChange(newValue);
    };

    @computed get hasMaximumReached() {
        const {maxOccurs, value} = this.props;

        return !!maxOccurs && value.length >= maxOccurs;
    }

    @computed get hasMinimumReached() {
        const {minOccurs, value} = this.props;

        return !!minOccurs && value.length <= minOccurs;
    }

    @computed get blockActions() {
        const blockActions = [];

        blockActions.push({
            type: 'button',
            icon: 'su-copy',
            label: translate('sulu_admin.copy'),
            onClick: this.handleCopyBlock,
        });

        if (!this.hasMinimumReached) {
            blockActions.push({
                type: 'button',
                icon: 'su-scissors',
                label: translate('sulu_admin.cut'),
                onClick: this.handleCutBlock,
            });
        }

        if (!this.hasMaximumReached) {
            blockActions.push({
                type: 'button',
                icon: 'su-duplicate',
                label: translate('sulu_admin.duplicate'),
                onClick: this.handleDuplicateBlock,
            });
        }

        if (!this.hasMinimumReached) {
            if (blockActions.length > 0) {
                blockActions.push({
                    type: 'divider',
                });
            }

            blockActions.push({
                type: 'button',
                icon: 'su-trash-alt',
                label: translate('sulu_admin.delete'),
                onClick: this.handleRemoveBlock,
            });
        }

        return blockActions;
    }

    renderAddButton = (aboveBlockIndex: number) => {
        const {addButtonText, pasteButtonText, disabled, value} = this.props;
        const isDividerButton = aboveBlockIndex < value.length - 1;

        const containerClass = classNames(
            blockCollectionStyles.addButtonContainer,
            {
                [blockCollectionStyles.addButtonDivider]: isDividerButton,
            }
        );

        return (
            <div className={containerClass}>
                <Button
                    className={blockCollectionStyles.addButton}
                    disabled={disabled || this.hasMaximumReached}
                    icon="su-plus"
                    onClick={this.handleAddBlock}
                    skin="secondary"
                    value={aboveBlockIndex + 1}
                >
                    {addButtonText ? addButtonText : translate('sulu_admin.add_block')}
                </Button>
                {this.pasteableBlocks.length > 0 && (
                    <Button
                        className={blockCollectionStyles.addButton}
                        disabled={disabled || this.hasMaximumReached}
                        icon="su-copy"
                        onClick={this.handlePasteBlocks}
                        skin="secondary"
                        value={aboveBlockIndex + 1}
                    >
                        {pasteButtonText
                            ? pasteButtonText
                            : translate('sulu_admin.paste_blocks', {count: this.pasteableBlocks.length})
                        }
                    </Button>
                )}
            </div>
        );
    };

    render() {
        const {
            collapsable,
            disabled,
            icons,
            movable,
            onSettingsClick,
            renderBlockContent,
            types,
            value,
        } = this.props;

        return (
            <section>
                <SortableBlockList
                    blockActions={this.blockActions}
                    disabled={disabled}
                    expandedBlocks={this.expandedBlocks}
                    generatedBlockIds={this.generatedBlockIds}
                    icons={icons}
                    lockAxis="y"
                    movable={movable}
                    onCollapse={collapsable ? this.handleCollapse : undefined}
                    onExpand={collapsable ? this.handleExpand : undefined}
                    onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    renderBlockContent={renderBlockContent}
                    renderDivider={this.renderAddButton}
                    types={types}
                    useDragHandle={true}
                    value={value}
                />
                {this.renderAddButton(value.length - 1)}
            </section>
        );
    }
}

export default BlockCollection;
