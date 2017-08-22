// @flow
import type {Element, ChildrenArray} from 'react';
import React from 'react';
import Row from './Row';
import tableStyles from './table.scss';

type Props = {
    children: ChildrenArray<Element<typeof Row>>,
};

export default class Header extends React.PureComponent<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <thead className={tableStyles.header}>
                {children}
            </thead>
        );
    }
}
