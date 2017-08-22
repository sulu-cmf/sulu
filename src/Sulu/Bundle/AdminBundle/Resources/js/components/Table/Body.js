// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Row from './Row';

type Props = {
    children: ChildrenArray<Element<typeof Row>>,
};

export default class Body extends React.PureComponent<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <tbody>
                {children}
            </tbody>
        );
    }
}
