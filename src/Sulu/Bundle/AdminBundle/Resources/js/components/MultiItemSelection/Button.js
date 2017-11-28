// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import buttonStyles from './button.scss';

type Props = {
    icon: string,
    onClick: () => void,
    location: 'left' | 'right',
};

export default class Button extends React.PureComponent<Props> {
    handleClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            icon,
            location,
        } = this.props;
        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[location]
        );

        return (
            <button
                type="button"
                onClick={this.handleClick}
                className={buttonClass}
            >
                <Icon name={icon} />
            </button>
        );
    }
}
