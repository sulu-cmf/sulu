// @flow
import React from 'react';
import classnames from 'classnames';
import Icon from '../Icon';
import optionStyles from './option.scss';

const SELECTED_ICON = 'check';

export default class Option extends React.PureComponent {
    props: {
        selected: boolean,
        disabled: boolean,
        focus: boolean,
        value: string,
        children?: string,
        onClick?: (s: string) => void,
    };

    static defaultProps = {
        disabled: false,
        selected: false,
        focus: false,
        value: '',
    };

    item: HTMLElement;
    button: HTMLButtonElement;

    componentDidMount() {
        if (this.props.focus) {
            window.requestAnimationFrame(() => {
                this.button.focus();
            });
        }
    }

    /** @public **/
    getOffsetTop() {
        return this.item.offsetTop;
    }

    handleButtonClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.value);
        }
    };

    setItem = (item: HTMLElement) => this.item = item;
    setButton = (button: HTMLButtonElement) => this.button = button;

    render() {
        const classNames = classnames({
            [optionStyles.option]: true,
            [optionStyles.selected]: this.props.selected,
        });

        return (
            <li ref={this.setItem}>
                <button
                    className={classNames}
                    ref={this.setButton}
                    onClick={this.handleButtonClick}
                    disabled={this.props.disabled}>
                    {this.props.selected ? <Icon className={optionStyles.icon} name={SELECTED_ICON} /> : ''}
                    {this.props.children}
                </button>
            </li>
        );
    }
}
