// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../../components/Icon';
import optionStyles from './option.scss';

const ICON_CHECKMARK = 'check';

type Props = {
    label: string | number,
    value: Object,
    onClick: (value: Object) => void,
    size?: string,
    selected?: boolean,
    disabled?: boolean,
};

export default class Option extends React.PureComponent<Props> {
    handleOnClick = () => {
        const {onClick} = this.props;

        onClick(this.props.value);
    };

    render() {
        const {
            size,
            label,
            selected,
            disabled,
        } = this.props;
        const optionClasses = classNames({
            [optionStyles.option]: true,
            [optionStyles[size]]: size,
            [optionStyles.isSelected]: selected,
        });

        return (
            <li className={optionClasses}>
                <button
                    disabled={disabled}
                    onClick={this.handleOnClick}>
                    {selected &&
                        <Icon name={ICON_CHECKMARK} className={optionStyles.selectedIcon} />
                    }
                    {label}
                </button>
            </li>
        );
    }
}
