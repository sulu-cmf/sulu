// @flow
import React from 'react';
import type {Node} from 'react';
import {SortableHandle} from 'react-sortable-hoc';
import Icon from '../Icon';
import itemStyles from './item.scss';

const DRAG_ICON = 'ellipsis-v';
const REMOVE_ICON = 'trash';

type Props = {
    id: string | number,
    index: number,
    children: Node,
    onRemove?: (id: string | number) => void,
};

export default class Item extends React.PureComponent<Props> {
    createDragHandle() {
        return SortableHandle(({children, className}) => (
            <span className={className}>{children}</span>
        ));
    }

    handleRemove = () => {
        if (this.props.onRemove) {
            this.props.onRemove(this.props.id);
        }
    };

    render() {
        const {
            index,
            onRemove,
            children,
        } = this.props;
        const DragHandle = this.createDragHandle();

        return (
            <div className={itemStyles.item}>
                <DragHandle className={itemStyles.dragHandle}>
                    <Icon name={DRAG_ICON} />
                    <span className={itemStyles.index}>{index}</span>
                </DragHandle>
                <div className={itemStyles.content}>
                    {children}
                </div>
                {onRemove &&
                    <button
                        className={itemStyles.removeButton}
                        onClick={this.handleRemove}
                    >
                        <Icon name={REMOVE_ICON} />
                    </button>
                }
            </div>
        );
    }
}
