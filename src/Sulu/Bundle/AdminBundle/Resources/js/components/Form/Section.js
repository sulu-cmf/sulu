// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Divider from '../Divider';
import Grid from '../Grid';
import type {ColSpan} from '../Grid';
import Field from './Field';
import gridStyles from './grid.scss';

type Props = {|
    children: ?ChildrenArray<?Element<typeof Field | typeof Section>>,
    colSpan: ColSpan,
    label?: string,
|};

export default class Section extends React.Component<Props> {
    static defaultProps = {
        colSpan: 12,
    };

    render() {
        const {children, label, colSpan} = this.props;

        const fields = React.Children.toArray(children);
        if (label || colSpan === 12) {
            fields.unshift(
                <Grid.Item colSpan={12} key={fields.length}>
                    <Divider>
                        {label}
                    </Divider>
                </Grid.Item>
            );
        }

        return (
            <Grid.Section className={gridStyles.gridSection} colSpan={colSpan}>
                {(((fields): any): ChildrenArray<Element<typeof Grid.Item | typeof Grid.Section>>)}
            </Grid.Section>
        );
    }
}
