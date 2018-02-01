// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SortableContainer} from 'react-sortable-hoc';
import SortableBlock from './SortableBlock';
import sortableBlockListStyles from './sortableBlockList.scss';
import type {RenderBlockContentCallback} from './types';

type Props = {
    blockTypes: Array<string>,
    expandedBlocks: Array<boolean>,
    onExpand: (index: number) => void,
    onCollapse: (index: number) => void,
    onRemove: (index: number) => void,
    onTypeChange?: (type: string | number, index: number) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<*>,
};

@observer
class SortableBlocks extends React.Component<Props> {
    handleExpand = (index: number) => {
        const {onExpand} = this.props;
        onExpand(index);
    };

    handleCollapse = (index: number) => {
        const {onCollapse} = this.props;
        onCollapse(index);
    };

    handleRemove = (index: number) => {
        const {onRemove} = this.props;
        onRemove(index);
    };

    handleTypeChange = (type: string | number, index: number) => {
        const {onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type, index);
        }
    };

    render() {
        const {blockTypes, expandedBlocks, renderBlockContent, types, value} = this.props;

        return (
            <div className={sortableBlockListStyles.sortableBlockList}>
                {value && value.map((block, index) => (
                    <SortableBlock
                        activeType={blockTypes[index]}
                        expanded={expandedBlocks[index]}
                        index={index}
                        key={index}
                        onExpand={this.handleExpand}
                        onCollapse={this.handleCollapse}
                        onRemove={this.handleRemove}
                        onTypeChange={this.handleTypeChange}
                        renderBlockContent={renderBlockContent}
                        sortIndex={index}
                        types={types}
                        value={block}
                    />
                ))}
            </div>
        );
    }
}

export default SortableContainer(SortableBlocks);
