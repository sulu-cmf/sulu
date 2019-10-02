// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
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

        if (!navigationItem.view) {
            return;
        }

        this.props.router.navigate(navigationItem.view);
        this.props.onNavigate(navigationItem.view);
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
                {navigationItems.map((navigationItem: NavigationItem) => (
                    <NavigationComponent.Item
                        active={this.isItemActive(navigationItem)}
                        icon={navigationItem.icon}
                        key={navigationItem.id}
                        title={navigationItem.label}
                        value={navigationItem.id}
                    >
                        {Array.isArray(navigationItem.items) &&
                            navigationItem.items.map((subNavigationItem) => (
                                <NavigationComponent.Item
                                    active={this.isItemActive(subNavigationItem)}
                                    key={subNavigationItem.id}
                                    title={subNavigationItem.label}
                                    value={subNavigationItem.id}
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
