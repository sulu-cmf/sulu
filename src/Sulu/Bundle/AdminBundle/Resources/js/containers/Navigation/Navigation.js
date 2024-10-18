// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed, isObservableArray} from 'mobx';
import {default as NavigationComponent} from '../../components/Navigation';
import Router from '../../services/Router';
import userStore from '../../stores/userStore';
import navigationRegistry from './registries/navigationRegistry';
import type {NavigationItem} from './types';

type Props = {
    appVersion: ?string,
    onLogout: () => void,
    onNavigate: (route: string) => void,
    onPinToggle: () => void,
    onProfileClick: () => void,
    pinned: boolean,
    router: Router,
    suluVersion: string,
};

const SULU_CHANGELOG_URL = 'https://github.com/sulu/sulu/releases';

@observer
class Navigation extends React.Component<Props> {
    @computed get username(): string {
        if (!userStore.loggedIn || !userStore.contact) {
            return '';
        }

        return userStore.contact.fullName;
    }

    @computed get userImage(): ?string {
        if (!userStore.loggedIn || !userStore.contact || !userStore.contact.avatar) {
            return undefined;
        }

        return userStore.contact.avatar.thumbnails['sulu-50x50'];
    }

    handleNavigationItemClick = (value: string) => {
        const navigationItem = navigationRegistry.get(value);
        const view = navigationItem.view;

        if (!view) {
            return;
        }

        this.props.router.navigate(view);
        this.props.onNavigate(view);
    };

    handleProfileEditClick = () => {
        this.props.onProfileClick();
    };

    handlePinToggle = () => {
        this.props.onPinToggle();
    };

    isItemActive = (navigationItem: NavigationItem) => {
        const {router} = this.props;

        if (!router.route) {
            return false;
        }

        return (navigationItem.view && router.route.name === navigationItem.view) ||
            (navigationItem.childViews && navigationItem.childViews.includes(router.route.name));
    };

    render() {
        const {appVersion, suluVersion} = this.props;
        const navigationItems = navigationRegistry.getAll();

        return (
            <NavigationComponent
                appVersion={appVersion}
                onItemClick={this.handleNavigationItemClick}
                onLogoutClick={this.props.onLogout}
                onPinToggle={this.handlePinToggle}
                onProfileClick={this.handleProfileEditClick}
                pinned={this.props.pinned}
                suluVersion={suluVersion}
                suluVersionLink={SULU_CHANGELOG_URL}
                title="Sulu" // TODO: Get this dynamically from server
                userImage={this.userImage}
                username={this.username}
            >
                {navigationItems.filter((item: NavigationItem) => item.visible).map((item: NavigationItem) => (
                    <NavigationComponent.Item
                        active={this.isItemActive(item)}
                        icon={item.icon}
                        key={item.id}
                        title={item.label}
                        value={item.id}
                    >
                        {(Array.isArray(item.items) || isObservableArray(item.items)) &&
                            // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
                            item.items.filter((subItem: NavigationItem) => subItem.visible).map((subItem) => (
                                <NavigationComponent.Item
                                    active={this.isItemActive(subItem)}
                                    key={subItem.id}
                                    title={subItem.label}
                                    value={subItem.id}
                                />
                            ))
                        }
                    </NavigationComponent.Item>
                ))}
            </NavigationComponent>
        );
    }
}

export default Navigation;
