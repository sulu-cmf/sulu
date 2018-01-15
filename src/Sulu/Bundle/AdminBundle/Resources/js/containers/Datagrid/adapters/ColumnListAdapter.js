// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import type {TreeItem} from '../types';
import ColumnList from '../../../components/ColumnList';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import AbstractAdapter from './AbstractAdapter';
import columnListAdapterStyles from './columnListAdapter.scss';

@observer
export default class ColumnListAdapter extends AbstractAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = TreeStructureStrategy;

    static defaultProps = {
        data: [],
    };

    handleItemClick = (id: string | number) => {
        const {onItemActivation} = this.props;
        if (onItemActivation) {
            onItemActivation(id);
        }
    };

    @computed get activeItemPath(): Array<string | number> {
        const {data} = this.props;
        const tree: Array<TreeItem> = data;

        const activeItemPath = [];

        this.prepareActiveItemPath(activeItemPath, tree);

        return activeItemPath;
    }

    prepareActiveItemPath(activeItemPath: Array<string | number>, tree: Array<TreeItem>) {
        for (let i = 0; i < tree.length; i++) {
            const item = tree[i];
            const {data, children} = item;

            if (data.id === this.props.active) {
                activeItemPath.unshift(data.id);
                return true;
            }

            const activeParent = this.prepareActiveItemPath(activeItemPath, children);

            if (activeParent) {
                activeItemPath.unshift(data.id);
                return true;
            }
        }
    }

    @computed get columnData(): Array<Array<Object>> {
        const {data} = this.props;
        const columns = [];
        const tree = ((data: any): Array<TreeItem>);

        this.prepareColumnLevel(columns, tree);
        this.prepareColumnChildren(columns, tree);

        return columns;
    }

    prepareColumnLevel(columns: Array<Array<Object>>, tree: Array<TreeItem>) {
        for (let i = 0; i < tree.length; i++) {
            const item = tree[i];
            const {data, children} = item;

            if (data.id === this.props.active) {
                this.prepareColumnChildren(columns, children);
                return true;
            }

            const activeParent = this.prepareColumnLevel(columns, children);

            if (activeParent) {
                this.prepareColumnChildren(columns, children);
                return true;
            }
        }
    }

    prepareColumnChildren(columns: Array<Array<Object>>, children: Array<TreeItem>) {
        columns.unshift(children.map((child) => child.data));
    }

    render() {
        const {loading} = this.props;

        return (
            <div className={columnListAdapterStyles.columnListAdapter}>
                <ColumnList onItemClick={this.handleItemClick} toolbarItems={[]}>
                    {this.columnData.map((items, index) => (
                        <ColumnList.Column key={index} loading={index >= this.columnData.length - 1 && loading}>
                            {items.map((item: Object) => (
                                // TODO: Don't access properties like "hasSub" or "title" directly
                                <ColumnList.Item
                                    id={item.id}
                                    key={item.id}
                                    hasChildren={item.hasSub}
                                    active={this.activeItemPath.includes(item.id)}
                                >
                                    {item.title}
                                </ColumnList.Item>
                            ))}
                        </ColumnList.Column>
                    ))}
                </ColumnList>
            </div>
        );
    }
}
