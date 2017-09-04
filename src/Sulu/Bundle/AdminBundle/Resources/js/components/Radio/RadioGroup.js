// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Radio from './Radio';

type Props = {
    value: string,
    onChange?: () => void,
    className?: string,
    children: ChildrenArray<Element<typeof Radio>>,
};

export default class RadioGroup extends React.PureComponent<Props> {
    render() {
        return (
            <div className={this.props.className}>
                {React.Children.map(this.props.children, (child) => {
                    return React.cloneElement(child, {
                        checked: !!this.props.value && child.props.value === this.props.value,
                        onChange: this.props.onChange,
                    });
                })}
            </div>
        );
    }
}
