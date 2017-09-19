// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../GenericSelect';
import GenericSelect from '../GenericSelect';

type Props = SelectProps & {
    value?: string,
    onChange?: (value: string) => void,
};

export default class Select extends React.PureComponent<Props> {
    static Action = GenericSelect.Action;

    static Option = GenericSelect.Option;

    static Divider = GenericSelect.Divider;

    get displayValue(): string {
        let displayValue = '';

        React.Children.forEach(this.props.children, (child: any) => {
            if (child.type !== Select.Option) {
                return;
            }

            if (!displayValue || this.props.value === child.props.value) {
                displayValue = child.props.children;
            }
        });

        return displayValue;
    }

    isOptionSelected = (option: Element<typeof Select.Option>): boolean => {
        return option.props.value === this.props.value && !option.props.disabled;
    };

    handleSelect = (value: string) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        const {icon, children} = this.props;

        return (
            <GenericSelect
                icon={icon}
                onSelect={this.handleSelect}
                displayValue={this.displayValue}
                isOptionSelected={this.isOptionSelected}
            >
                {children}
            </GenericSelect>
        );
    }
}
