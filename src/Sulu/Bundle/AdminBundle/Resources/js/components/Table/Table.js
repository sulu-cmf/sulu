// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Icon from '../Icon';
import Header from './Header';
import Body from './Body';
import type {ButtonConfig, SelectMode} from './types';
import tableStyles from './table.scss';

const PLACEHOLDER_ICON = 'battery-quarter';

type Props = {
    children: ChildrenArray<Element<typeof Header | typeof Body>>,
    /** List of buttons to apply action handlers to every row (e.g. edit row) */
    buttons?: Array<ButtonConfig>,
    /** Can be set to "single" or "multiple". Defaults is "none". */
    selectMode?: SelectMode,
    /**
     * Callback function to notify about selection and deselection of a row.
     * If the "id" prop is set on the row, the "rowId" corresponds to that, else it is the index of the row.
     */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    /** Called when the "select all" checkbox in the header was clicked. Returns the checked state. */
    onAllSelectionChange?: (checked: boolean) => void,
    /** Text shown when the table has no entries */
    placeholderText?: string,
};

@observer
export default class Table extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    cloneHeader = (originalHeader?: Element<typeof Header>, allSelected: boolean) => {
        if (!originalHeader) {
            return null;
        }

        return React.cloneElement(
            originalHeader,
            {
                allSelected: allSelected,
                buttons: this.props.buttons,
                selectMode: this.props.selectMode,
                onAllSelectionChange: this.handleAllSelectionChange,
            }
        );
    };

    cloneBody = (originalBody?: Element<typeof Body>) => {
        if (!originalBody) {
            return null;
        }

        return React.cloneElement(
            originalBody,
            {
                buttons: this.props.buttons,
                selectMode: this.props.selectMode,
                onRowSelectionChange: this.handleRowSelectionChange,
            }
        );
    };

    checkAllRowsSelected = (body: Element<typeof Body>) => {
        const rows = body.props.children;
        const rowSelections = React.Children.map(rows, (row) => row.props.selected);

        return !rowSelections.includes(false);
    };

    createTablePlaceholderArea = () => {
        const {placeholderText} = this.props;

        return (
            <div className={tableStyles.tablePlaceholderArea}>
                <Icon name={PLACEHOLDER_ICON} className={tableStyles.tablePlaceholderIcon} />
                {placeholderText &&
                    <div className={tableStyles.tablePlaceholderText}>
                        {placeholderText}
                    </div>
                }
            </div>
        );
    };

    handleAllSelectionChange = (checked: boolean) => {
        if (this.props.onAllSelectionChange) {
            this.props.onAllSelectionChange(checked);
        }
    };

    handleRowSelectionChange = (rowId: string | number, selected?: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
        }
    };

    render() {
        const {children} = this.props;
        let body;
        let header;

        React.Children.forEach(children, (child: Element<typeof Header | typeof Body>) => {
            switch (child.type) {
                case Header:
                    header = child;
                    break;
                case Body:
                    body = child;
                    break;
                default:
                    throw new Error(
                        'The Table component only accepts the following children types: ' +
                        [Header.name, Body.name].join(', ')
                    );
            }
        });

        const clonedBody = this.cloneBody(body);
        const emptyBody = (clonedBody && React.Children.count(clonedBody.props.children) === 0);
        const allRowsSelected = (clonedBody && !emptyBody) ? this.checkAllRowsSelected(clonedBody) : false;
        const clonedHeader = this.cloneHeader(header, allRowsSelected);

        return (
            <div className={tableStyles.tableContainer}>
                <table className={tableStyles.table}>
                    {clonedHeader}
                    {clonedBody}
                </table>
                {emptyBody &&
                    this.createTablePlaceholderArea()
                }
            </div>
        );
    }
}
