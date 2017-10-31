// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Icon from '../../components/Icon';
import Toolbar from '../../components/Toolbar';
import ToolbarStore from './stores/ToolbarStore';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';
import toolbarStyles from './toolbar.scss';
import type {ToolbarProps} from './types';

const BACK_BUTTON_ICON = 'arrow-left';
const LOCALE_SELECT_SIZE = 'small';
const NAVIGATION_BUTTON_ICON = 'bars';

const ToolbarItemTypes = {
    Button: 'button',
    Select: 'select',
    Dropdown: 'dropdown',
};

function getItemComponentByType(itemConfig, key) {
    let item;

    switch (itemConfig.type) {
        case ToolbarItemTypes.Select:
            item = (<Toolbar.Select {...itemConfig} key={key} />);
            break;
        case ToolbarItemTypes.Dropdown:
            item = (<Toolbar.Dropdown {...itemConfig} key={key} />);
            break;
        default:
            item = (<Toolbar.Button {...itemConfig} key={key} />);
    }

    return item;
}

@observer
export default class ToolbarContainer extends React.PureComponent<*> {
    props: ToolbarProps;

    toolbarStore: ToolbarStore;

    componentWillMount() {
        this.setStore(this.props.storeKey);
    }

    componentWillUpdate(nextProps: ToolbarProps) {
        if (nextProps.storeKey) {
            this.setStore(nextProps.storeKey);
        }
    }

    setStore = (storeKey: string = DEFAULT_STORE_KEY) => {
        if (toolbarStorePool.hasStore(storeKey)) {
            this.toolbarStore = toolbarStorePool.getStore(storeKey);
        } else {
            this.toolbarStore = toolbarStorePool.createStore(storeKey);
        }
    };

    handleNavigationButtonClick = () => {
        if (this.props.onNavigationButtonClick) {
            this.props.onNavigationButtonClick();
        }
    };

    render() {
        const {onNavigationButtonClick} = this.props;
        const loadingItems = this.toolbarStore.getItemsConfig().filter((item) => item.loading);
        const disableAllButtons = this.toolbarStore.disableAll || loadingItems.length > 0;
        const backButtonConfig = this.toolbarStore.getBackButtonConfig();
        const itemsConfig = this.toolbarStore.getItemsConfig();

        if (disableAllButtons) {
            if (backButtonConfig) {
                backButtonConfig.disabled = true;
            }

            itemsConfig.forEach((item) => {
                item.disabled = true;
            });
        }

        return (
            <Toolbar>
                <Toolbar.Controls>
                    {onNavigationButtonClick &&
                    <Toolbar.Button
                        onClick={this.handleNavigationButtonClick}
                        skin="primary"
                        icon={NAVIGATION_BUTTON_ICON}
                    />
                    }
                    {this.toolbarStore.hasBackButtonConfig() &&
                    <Toolbar.Button
                        {...backButtonConfig}
                        icon={BACK_BUTTON_ICON}
                    />
                    }
                    {this.toolbarStore.hasItemsConfig() &&
                    <Toolbar.Items>
                        {itemsConfig.map((itemConfig, index) => getItemComponentByType(itemConfig, index))}
                    </Toolbar.Items>
                    }
                </Toolbar.Controls>
                <Toolbar.Controls>
                    {this.toolbarStore.hasIconsConfig() &&
                    <div className={toolbarStyles.icons}>
                        {this.toolbarStore.getIconsConfig().map((icon) => (
                            <Icon
                                key={icon}
                                name={icon}
                                className={toolbarStyles.icon}
                            />
                        ))}
                    </div>
                    }
                    {this.toolbarStore.hasLocaleConfig() &&
                    <div className={toolbarStyles.locale}>
                        <Toolbar.Select
                            size={LOCALE_SELECT_SIZE}
                            {...this.toolbarStore.getLocaleConfig()}
                        />
                    </div>
                    }
                </Toolbar.Controls>
            </Toolbar>
        );
    }
}
