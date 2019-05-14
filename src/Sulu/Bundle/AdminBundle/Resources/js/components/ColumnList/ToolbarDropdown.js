// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../Icon';
import ArrowMenu from '../ArrowMenu';
import type {ToolbarDropdown as ToolbarDropdownProps} from './types';
import toolbarStyles from './toolbar.scss';
import toolbarDropdownStyles from './toolbarDropdown.scss';

@observer
class ToolbarDropdown extends React.Component<ToolbarDropdownProps> {
    static defaultProps = {
        skin: 'primary',
    };

    @observable open: boolean = false;

    @action handleClick = () => {
        this.open = true;
    };

    @action handleMenuClose = () => {
        this.open = false;
    };

    renderButton() {
        const {icon, skin} = this.props;
        const className = classNames(
            toolbarStyles.item,
            toolbarStyles[skin]
        );
        return (
            <button className={className} onClick={this.handleClick}>
                <Icon name={icon} />
                <Icon className={toolbarDropdownStyles.buttonArrowIcon} name="su-angle-down" />
            </button>
        );
    }

    render() {
        return (
            <Fragment>
                <ArrowMenu anchorElement={this.renderButton()} onClose={this.handleMenuClose} open={this.open}>
                    <ArrowMenu.Section>
                        {
                            this.props.options.map(({disabled, label, onClick}, index) => (
                                <ArrowMenu.Action disabled={disabled} key={index} onClick={onClick}>
                                    {label}
                                </ArrowMenu.Action>
                            ))
                        }
                    </ArrowMenu.Section>
                </ArrowMenu>
            </Fragment>
        );
    }
}

export default ToolbarDropdown;
