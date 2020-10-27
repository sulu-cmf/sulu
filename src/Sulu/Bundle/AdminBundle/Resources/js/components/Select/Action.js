// @flow
import React from 'react';
import type {ElementRef} from "react";
import actionStyles from './action.scss';

type Props<T> = {|
    afterAction?: () => void,
    buttonRef?: (buttonRef: ?ElementRef<'button'>) => void,
    children: string,
    onClick: (value: ?T) => void,
    requestFocus?: () => void,
    value?: T,
|};

export default class Action<T> extends React.PureComponent<Props<T>> {
    handleButtonClick = () => {
        const {
            onClick,
            afterAction,
            value,
        } = this.props;

        onClick(value);

        if (afterAction) {
            afterAction();
        }
    };

    setButtonRef = (ref: ?ElementRef<'button'>) => {
        const {buttonRef} = this.props;

        if (buttonRef) {
            buttonRef(ref);
        }
    };

    handleMouseMove = (event: MouseEvent) => {
        if (this.props.requestFocus) {
            this.props.requestFocus();
        }
    };

    render() {
        return (
            <li onMouseMove={this.handleMouseMove}>
                <button
                    className={actionStyles.action}
                    onClick={this.handleButtonClick}
                    ref={this.setButtonRef}
                >
                    {this.props.children}
                </button>
            </li>
        );
    }
}
