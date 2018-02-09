// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Pagination from '../../../components/Pagination';
import Table from '../../../components/Table';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class TableAdapter extends AbstractAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static defaultProps = {
        data: [],
    };

    renderCells(item: Object, schemaKeys: Array<string>) {
        return schemaKeys.map((schemaKey) => {
            // TODO: Remove this when a datafield mapping is built
            if (typeof item[schemaKey] === 'object') {
                return <Table.Cell key={item.id + schemaKey}>Object!</Table.Cell>;
            }

            return (
                <Table.Cell key={item.id + schemaKey}>{item[schemaKey]}</Table.Cell>
            );
        });
    }

    render() {
        const {
            data,
            loading,
            onItemClick,
            onAllSelectionChange,
            onItemSelectionChange,
            onPageChange,
            page,
            pageCount,
            schema,
            selections,
        } = this.props;
        const schemaKeys = Object.keys(schema);
        const buttons = [];

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: (rowId) => onItemClick(rowId),
            });
        }

        return (
            <Pagination
                total={pageCount}
                current={page}
                loading={loading}
                onChange={onPageChange}
            >
                <Table
                    buttons={buttons}
                    selectMode="multiple"
                    onRowSelectionChange={onItemSelectionChange}
                    onAllSelectionChange={onAllSelectionChange}
                >
                    <Table.Header>
                        {schemaKeys.map((schemaKey) => (
                            <Table.HeaderCell key={schemaKey}>{schemaKey}</Table.HeaderCell>
                        ))}
                    </Table.Header>
                    <Table.Body>
                        {data.map((item) => (
                            <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                                {this.renderCells(item, schemaKeys)}
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Pagination>
        );
    }
}
