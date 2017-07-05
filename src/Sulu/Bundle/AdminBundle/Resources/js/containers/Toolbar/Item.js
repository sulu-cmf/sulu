// @flow
import React from 'react';
import classNames from 'classnames';
import type {Item as ItemType} from './types';
import Icon from '../../components/Icon';
import itemStyles from './item.scss';

export default class Item extends React.PureComponent {
    props: ItemType;

    static defaultProps = {
        enabled: true,
    };

    handleClick = () => {
        if (this.props.enabled && this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        const liClassNames = classNames({
            [`${itemStyles['item']}`]: true,
            [`${itemStyles['item-disabled']}`]: !this.props.enabled,
        });

        return (
            <button className={liClassNames} onClick={this.handleClick}>
                <Icon className={itemStyles.icon} name={this.props.icon} />
                <span className={itemStyles.title}>{this.props.title}</span>
            </button>
        );
    }
}
