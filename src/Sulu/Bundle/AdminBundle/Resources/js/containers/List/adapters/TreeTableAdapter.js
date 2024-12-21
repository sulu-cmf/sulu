// @flow
import {action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Table from '../../../components/Table';
import Loader from '../../../components/Loader';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import DefaultLoadingStrategy from '../loadingStrategies/DefaultLoadingStrategy';
import Pagination from '../../../components/Pagination';
import AbstractTableAdapter from './AbstractTableAdapter';
import type {ListAdapterProps} from '../types';

@observer
class TreeTableAdapter extends AbstractTableAdapter {
    static LoadingStrategy = DefaultLoadingStrategy;

    static StructureStrategy = TreeStructureStrategy;

    static icon = 'su-tree-list';

    @action handleRowCollapse = (rowId: string | number) => {
        this.props.onItemDeactivate(rowId);
    };

    @action handleRowExpand = (rowId: string | number) => {
        this.props.onItemActivate(rowId);
    };

    getButtons = (item: ?Object) => {
        const {
            itemActionsProvider,
            onItemClick,
            onItemAdd,
        } = this.props;

        const {
            data: {
                _permissions: {
                    add: addPermission = true,
                    edit: editPermission = true,
                    view: viewPermission = true,
                } = {},
            } = {},
        } = item || {};

        const buttons = [];

        if (onItemClick) {
            buttons.push({
                disabled: !viewPermission,
                icon: editPermission ? 'su-pen' : 'su-eye',
                onClick: onItemClick,
            });
        }

        if (onItemAdd) {
            buttons.push({
                disabled: !addPermission,
                icon: 'su-plus-circle',
                onClick: onItemAdd,
            });
        }

        if (itemActionsProvider) {
            buttons.push(...itemActionsProvider(item?.data));
        }

        return buttons;
    };

    renderRows(items: Array<*>, depth: number = 0) {
        const rows = [];
        const {
            disabledIds,
            selections,
        } = this.props;

        for (const item of items) {
            const {data, hasChildren} = item;

            rows.push(
                <Table.Row
                    buttons={this.getButtons(item)}
                    depth={depth}
                    disabled={disabledIds.includes(data.id)}
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

    handlePageChange = (page: number) => {
        const {
            onPageChange,
            onItemActivate,
        } = this.props;

        onItemActivate(undefined);

        onPageChange(page);
    };

    render() {
        const {
            active,
            data,
            limit,
            loading,
            onAllSelectionChange,
            onItemSelectionChange,
            onLimitChange,
            adapterOptions: {
                show_header: showHeaderAdapterOption = true,
                skin = 'dark',
            } = {},
            options: {
                showHeader: showHeaderOption = true,
            },
            page,
            pageCount,
            paginated,
        }: ListAdapterProps = this.props;

        if (!active && loading) {
            return <Loader />;
        }

        const table = (
            <Table
                buttons={this.getButtons()}
                onAllSelectionChange={onAllSelectionChange}
                onRowCollapse={this.handleRowCollapse}
                onRowExpand={this.handleRowExpand}
                onRowSelectionChange={onItemSelectionChange}
                selectInFirstCell={true}
                selectMode="multiple"
                skin={(skin: any)}
            >
                {showHeaderAdapterOption && showHeaderOption ?
                    <Table.Header>
                        {this.renderHeaderCells()}
                    </Table.Header>
                    : null
                }
                <Table.Body>
                    {this.renderRows(data)}
                </Table.Body>
            </Table>
        );

        if (!paginated || (page === 1 && data.length === 0)) {
            return table;
        }

        if (pageCount === undefined) {
            return table;
        }

        return (
            <Pagination
                currentLimit={limit}
                currentPage={page}
                loading={loading}
                onLimitChange={onLimitChange}
                onPageChange={this.handlePageChange}
                totalPages={pageCount}
            >
                {table}
            </Pagination>
        );
    }
}

export default TreeTableAdapter;
