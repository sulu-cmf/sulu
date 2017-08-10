// @flow
import Backdrop from '../../components/Backdrop';
import Option from './Option';
import React from 'react';
import classNames from 'classnames';
import optionListStyles from './optionList.scss';

export default class OptionList extends React.PureComponent {
    props: {
        onClick: (option: Object) => void,
        value?: string | number,
        size?: string,
        onRequestClose?: () => void,
        options: Array<Object>,
    };

    handleOptionClick = (option: Object) => {
        if (this.props.onClick) {
            this.props.onClick(option);
        }

        if (this.props.onRequestClose) {
            this.props.onRequestClose();
        }
    };

    handleBackdropClick = () => {
        if (this.props.onRequestClose) {
            this.props.onRequestClose();
        }
    };

    render() {
        const {
            size,
            value,
            options,
        } = this.props;
        const optionListClasses = classNames({
            [optionListStyles.optionList]: true,
            [optionListStyles[size]]: size,
        });

        return (
            <div>
                <ul className={optionListClasses}>
                    {
                        options.map((option, index: number) => {
                            const isSelected = option.value ? option.value === value : false;

                            return (
                                <Option
                                    key={index}
                                    size={size}
                                    value={option}
                                    label={option.label}
                                    disabled={option.disabled}
                                    selected={isSelected}
                                    onClick={this.handleOptionClick} />
                            );
                        })
                    }
                </ul>
                <Backdrop isOpen={true} onClick={this.handleBackdropClick} isVisible={false} />
            </div>
        );
    }
}
