// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ItemButtonConfig, ToolbarItemConfig} from './types';
import columnStyles from './column.scss';

type Props = {
    active: boolean,
    buttons?: Array<ItemButtonConfig>,
    children?: ChildrenArray<Element<typeof Item>>,
    index?: number,
    loading: boolean,
    onActive?: (index?: number) => void,
    onItemClick?: (id: string | number) => void,
    toolbarItems: Array<ToolbarItemConfig>,
};

export default class Column extends React.Component<Props> {
    static defaultProps = {
        active: false,
        loading: false,
        toolbarItems: [],
    };

    cloneItems = (originalItems?: ChildrenArray<Element<typeof Item>>) => {
        if (!originalItems) {
            return null;
        }

        const {buttons, onItemClick} = this.props;

        return React.Children.map(originalItems, (column) => {
            return React.cloneElement(
                column,
                {
                    buttons: buttons,
                    onClick: onItemClick,
                }
            );
        });
    };

    handleMouseEnter = () => {
        const {index, onActive} = this.props;

        if (!onActive) {
            return;
        }

        onActive(index);
    };

    render() {
        const {children, active, index, loading, toolbarItems} = this.props;

        const columnContainerClass = classNames(
            columnStyles.columnContainer,
            {
                [columnStyles.active]: active,
            }
        );

        const columnClass = classNames(
            columnStyles.column,
            {
                [columnStyles.loading]: loading,
            }
        );

        return (
            <div onMouseEnter={this.handleMouseEnter} className={columnContainerClass}>
                <Toolbar active={active} columnIndex={index} toolbarItems={toolbarItems} />
                <div className={columnClass}>
                    {loading ? <Loader /> : this.cloneItems(children)}
                </div>
            </div>
        );
    }
}

