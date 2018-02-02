// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import React from 'react';
import type {DropdownOption, Dropdown as DropdownProps} from './types';
import Button from './Button';
import OptionList from './OptionList';
import dropdownStyles from './dropdown.scss';

@observer
export default class Dropdown extends React.Component<DropdownProps> {
    @observable open = false;

    @action close = () => {
        this.open = false;
    };

    @action toggle = () => {
        this.open = !this.open;
    };

    componentWillReceiveProps = (nextProps: DropdownProps) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleButtonClick = () => {
        this.toggle();
    };

    handleOptionListClick = (option: DropdownOption) => {
        if (option.onClick) {
            option.onClick();
        }
    };

    handleOptionListClose = () => {
        this.close();
    };

    render() {
        const {
            icon,
            size,
            label,
            options,
            disabled,
            loading,
        } = this.props;
        const dropdownClass = classNames(
            dropdownStyles.dropdown,
            {
                [dropdownStyles[size]]: size,
            }
        );

        return (
            <div className={dropdownClass}>
                <Button
                    icon={icon}
                    size={size}
                    disabled={disabled}
                    value={label}
                    onClick={this.handleButtonClick}
                    active={this.open}
                    hasOptions={true}
                    loading={loading}
                />
                {this.open &&
                    <OptionList
                        options={options}
                        onOptionClick={this.handleOptionListClick}
                        onClose={this.handleOptionListClose}
                    />
                }
            </div>
        );
    }
}
