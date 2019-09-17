// @flow
import {observer} from 'mobx-react';
import {action, computed} from 'mobx';
import React, {Fragment} from 'react';
import ToolbarComponent from '../../components/Toolbar';
import Snackbar from '../../components/Snackbar';
import ToolbarStore from './stores/ToolbarStore';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/toolbarStorePool';
import toolbarStyles from './toolbar.scss';
import type {ToolbarProps} from './types';

const LOCALE_SELECT_SIZE = 'small';

const SUCCESS_ICON = 'su-check';

const ToolbarItemTypes = {
    Button: 'button',
    Dropdown: 'dropdown',
    Select: 'select',
    Toggler: 'toggler',
};

function getItemComponentByType(itemConfig, key) {
    switch (itemConfig.type) {
        case ToolbarItemTypes.Select:
            const {type: selectType, ...selectConfig} = itemConfig;
            return <ToolbarComponent.Select {...selectConfig} key={key} />;
        case ToolbarItemTypes.Dropdown:
            const {type: dropdownType, ...dropdownConfig} = itemConfig;
            return <ToolbarComponent.Dropdown {...dropdownConfig} key={key} />;
        case ToolbarItemTypes.Toggler:
            const {type: togglerType, ...togglerConfig} = itemConfig;
            return <ToolbarComponent.Toggler {...togglerConfig} key={key} />;
        default:
            const {type: buttonType, ...buttonConfig} = itemConfig;
            return <ToolbarComponent.Button {...buttonConfig} key={key} />;
    }
}

@observer
class Toolbar extends React.Component<ToolbarProps> {
    static defaultProps = {
        navigationOpen: false,
    };

    toolbarStore: ToolbarStore;

    constructor(props: *) {
        super(props);

        this.setStore(this.props.storeKey);
    }

    componentDidUpdate(nextProps: ToolbarProps) {
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

    @action handleErrorSnackbarCloseClick = () => {
        this.toolbarStore.errors.pop();
    };

    @computed get disableAllButtons() {
        const loadingItems = this.toolbarStore.getItemsConfig().filter((item) => item.loading);
        return this.toolbarStore.disableAll || loadingItems.length > 0;
    }

    @computed get backButtonConfig() {
        const backButtonConfig = this.toolbarStore.getBackButtonConfig();

        if (!backButtonConfig) {
            return undefined;
        }

        if (this.disableAllButtons) {
            backButtonConfig.disabled = true;
        }

        return backButtonConfig;
    }

    @computed get itemsConfig() {
        const itemsConfig = this.toolbarStore.getItemsConfig();

        if (this.disableAllButtons) {
            itemsConfig.forEach((item) => {
                item.disabled = true;
            });
        }

        return itemsConfig;
    }

    render() {
        const {onNavigationButtonClick, navigationOpen} = this.props;
        const {errors, showSuccess, warnings} = this.toolbarStore;

        return (
            <Fragment>
                <Snackbar
                    message={errors[errors.length - 1]}
                    onCloseClick={this.handleErrorSnackbarCloseClick}
                    type="error"
                    visible={errors.length > 0}
                />
                <Snackbar
                    message={warnings[warnings.length - 1]}
                    type="warning"
                    visible={warnings.length > 0}
                />
                <ToolbarComponent>
                    <ToolbarComponent.Controls grow={true}>
                        {!!onNavigationButtonClick &&
                            <ToolbarComponent.Button
                                disabled={!onNavigationButtonClick}
                                icon={showSuccess
                                    ? SUCCESS_ICON
                                    : navigationOpen
                                        ? 'su-times'
                                        : 'su-bars'
                                }
                                onClick={onNavigationButtonClick}
                                primary={true}
                                success={showSuccess}
                            />
                        }
                        {!!this.backButtonConfig &&
                            <ToolbarComponent.Button
                                {...this.backButtonConfig}
                                icon={!onNavigationButtonClick && showSuccess ? SUCCESS_ICON : 'su-angle-left'}
                                success={!onNavigationButtonClick && showSuccess}
                            />
                        }
                        {this.toolbarStore.hasItemsConfig() &&
                            <ToolbarComponent.Items>
                                {this.itemsConfig.map((itemConfig, index) => getItemComponentByType(itemConfig, index))}
                            </ToolbarComponent.Items>
                        }
                    </ToolbarComponent.Controls>
                    <ToolbarComponent.Controls>
                        {this.toolbarStore.hasIconsConfig() &&
                            <ToolbarComponent.Icons>
                                {this.toolbarStore.getIconsConfig().map((icon) => icon)}
                            </ToolbarComponent.Icons>
                        }
                        {this.toolbarStore.hasLocaleConfig() &&
                            <ToolbarComponent.Select
                                className={toolbarStyles.locale}
                                size={LOCALE_SELECT_SIZE}
                                {...this.toolbarStore.getLocaleConfig()}
                            />
                        }
                    </ToolbarComponent.Controls>
                </ToolbarComponent>
            </Fragment>
        );
    }
}

export default Toolbar;
