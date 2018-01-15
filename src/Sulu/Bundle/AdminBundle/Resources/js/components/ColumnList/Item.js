// @flow
import React from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import ItemButton from './ItemButton';
import type {ItemButtonConfig} from './types';
import itemStyles from './item.scss';

type Props = {
    id: string | number,
    children: string,
    active: boolean,
    hasChildren: boolean,
    buttons?: Array<ItemButtonConfig>,
    onClick?: (id: string | number) => void,
};

export default class Item extends React.Component<Props> {
    static defaultProps = {
        active: false,
        hasChildren: false,
    };

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id);
        }
    };

    createButtons = () => {
        const {buttons, id} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ItemButtonConfig, index: number) => {
            const key = `button-${index}`;

            return (
                <ItemButton id={id} key={key} config={button} />
            );
        });
    };

    render() {
        const {children, active, hasChildren} = this.props;

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.active]: active,
            }
        );

        return (
            <div onClick={this.handleClick} className={itemClass}>
                <span className={itemStyles.buttons}>
                    {this.createButtons()}
                </span>
                <span className={itemStyles.text}>
                    <CroppedText>{children}</CroppedText>
                </span>
                {hasChildren &&
                    <Icon className={itemStyles.children} name="chevron-right" />
                }
            </div>
        );
    }
}
