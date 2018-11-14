// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../Select';
import Select from '../Select';
import {translate} from '../../utils/Translator';

type Props<T: string | number> = SelectProps & {
    onChange?: (value: T) => void,
    value: ?T,
};

export default class SingleSelect<T: string | number> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        disabled: false,
        skin: 'default',
    };

    static Action = Select.Action;
    static Option = Select.Option;
    static Divider = Select.Divider;

    get displayValue(): string {
        let displayValue = translate('sulu_admin.please_choose');

        React.Children.forEach(this.props.children, (child: any) => {
            if (child.type !== SingleSelect.Option) {
                return;
            }

            if (this.props.value == child.props.value) {
                displayValue = child.props.children;
            }
        });

        return displayValue;
    }

    isOptionSelected = (option: Element<typeof SingleSelect.Option>): boolean => {
        const {value} = this.props;

        if (value == null) {
            return false;
        }

        return option.props.value === value && !option.props.disabled;
    };

    handleSelect = (value: T) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        const {children, disabled, icon, skin} = this.props;

        return (
            <Select
                disabled={disabled}
                displayValue={this.displayValue}
                icon={icon}
                isOptionSelected={this.isOptionSelected}
                onSelect={this.handleSelect}
                skin={skin}
            >
                {children}
            </Select>
        );
    }
}
