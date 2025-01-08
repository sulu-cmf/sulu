// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/utils';
import {Icon, ArrowMenu} from 'sulu-admin-bundle/components';
import webspaceSelectStyles from './webspaceSelect.scss';
import type {ChildrenArray, Element} from 'react';

type Props = {
    children: ChildrenArray<Element<typeof ArrowMenu.Item>>,
    onChange: (value: string) => void,
    value: string,
};

@observer
class WebspaceSelect extends React.Component<Props> {
    static Item = ArrowMenu.Item;

    @observable open: boolean = false;

    @action openMenu = () => {
        this.open = true;
    };

    @action closeMenu = () => {
        this.open = false;
    };

    handleButtonClick = this.openMenu;

    handleMenuClose = this.closeMenu;

    handleChange = (value: string) => {
        this.closeMenu();
        this.props.onChange(value);
    };

    get displayValue(): string {
        const {children, value} = this.props;
        let displayValue = '';

        React.Children.forEach(children, (child: any) => {
            if (value === child.props.value) {
                displayValue = child.props.children;
            }
        });

        return displayValue;
    }

    renderButton() {
        return (
            <div className={webspaceSelectStyles.webspaceSelect}>
                <button
                    className={webspaceSelectStyles.button}
                    onClick={this.handleButtonClick}
                    type="button"
                >
                    <Icon className={webspaceSelectStyles.buttonIcon} name="su-webspace" />
                    <span className={webspaceSelectStyles.buttonValue}>{this.displayValue}</span>
                    <Icon className={webspaceSelectStyles.buttonIcon} name="su-angle-down" />
                </button>
            </div>
        );
    }

    render() {
        const {
            value,
            children,
        } = this.props;

        return (
            <ArrowMenu anchorElement={this.renderButton()} onClose={this.handleMenuClose} open={this.open}>
                <ArrowMenu.SingleItemSection
                    icon="su-webspace"
                    onChange={this.handleChange}
                    title={translate('sulu_page.webspaces')}
                    value={value}
                >
                    {children}
                </ArrowMenu.SingleItemSection>
            </ArrowMenu>
        );
    }
}

export default WebspaceSelect;
