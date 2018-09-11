// @flow
import {action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Table from '../../../components/Table';
import Loader from '../../../components/Loader';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import AbstractTableAdapter from './AbstractTableAdapter';

@observer
export default class TreeTableAdapter extends AbstractTableAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = TreeStructureStrategy;

    static icon = 'su-tree-list';

    @action handleRowCollapse = (rowId: string | number) => {
        this.props.onItemDeactivate(rowId);
    };

    @action handleRowExpand = (rowId: string | number) => {
        this.props.onItemActivate(rowId);
    };

    renderRows(items: Array<*>, depth: number = 0) {
        const rows = [];
        const {
            selections,
        } = this.props;

        for (const item of items) {
            const {data, hasChildren} = item;

            rows.push(
                <Table.Row
                    depth={depth}
                    expanded={item.children.length > 0}
                    hasChildren={hasChildren}
                    id={data.id}
                    isLoading={this.props.active === data.id && this.props.loading}
                    key={data.id}
                    selected={selections.includes(data.id)}
                >
                    {this.renderCells(data)}
                </Table.Row>
            );

            rows.push(...this.renderRows(item.children, depth + 1));
        }

        return rows;
    }

    render() {
        const {
            active,
            data,
            loading,
            onItemClick,
            onItemAdd,
            onAllSelectionChange,
            onItemSelectionChange,
            options: {
                showHeader = true,
            },
        } = this.props;
        const buttons = [];

        if (!active && loading) {
            return <Loader />;
        }

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: onItemClick,
            });
        }

        if (onItemAdd) {
            buttons.push({
                icon: 'su-plus-circle',
                onClick: onItemAdd,
            });
        }

        return (
            <Table
                buttons={buttons}
                onAllSelectionChange={onAllSelectionChange}
                onRowCollapse={this.handleRowCollapse}
                onRowExpand={this.handleRowExpand}
                onRowSelectionChange={onItemSelectionChange}
                selectInFirstCell={true}
                selectMode="multiple"
            >
                {showHeader &&
                    <Table.Header>
                        {this.renderHeaderCells()}
                    </Table.Header>
                }
                <Table.Body>
                    {this.renderRows(data)}
                </Table.Body>
            </Table>
        );
    }
}
