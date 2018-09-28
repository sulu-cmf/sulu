// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Icon from '../Icon';
import Button from '../Button';
import ButtonGroup from '../ButtonGroup';
import Item from './Item';
import navigationStyles from './navigation.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    title: string,
    username: string,
    userImage: ?string,
    onLogoutClick: () => void,
    onPinClick?: () => void,
    onProfileClick: () => void,
    suluVersion: string,
    suluVersionLink: string,
    appVersion: ?string,
    appVersionLink?: string,
};

@observer
export default class Navigation extends React.Component<Props> {
    static defaultProps = {
        appVersion: undefined,
        userImage: undefined,
    };

    static Item = Item;

    @observable expandedChild: * = null;

    @action setExpandedChild(value: *) {
        this.expandedChild = value;
    }

    componentWillReceiveProps(newProps: Props) {
        this.findDefaultExpandedChild(newProps.children);
    }

    constructor(props: Props) {
        super(props);

        this.findDefaultExpandedChild(this.props.children);
    }

    findDefaultExpandedChild = (children: ChildrenArray<Element<typeof Item>>) => {
        let newExpandedChild = null;
        React.Children.forEach(children, (child) => {
            if (child.props.children) {
                React.Children.forEach(child.props.children, (subChild) => {
                    if (subChild.props.active) {
                        newExpandedChild = child.props.value;
                    }
                });
            }
        });

        this.setExpandedChild(newExpandedChild);
    };

    handleExpand = (value: *) => {
        if (this.expandedChild === value) {
            this.setExpandedChild(null);

            return;
        }

        this.setExpandedChild(value);
    };

    cloneChildren(): ChildrenArray<Element<typeof Item>> {
        return React.Children.map(this.props.children, (child) => {
            return React.cloneElement(child, {
                onClick: child.props.children ? this.handleExpand : child.props.onClick,
                expanded: child.props.value === this.expandedChild,
            });
        });
    }

    renderUserImage() {
        const {userImage, username, onProfileClick} = this.props;

        if (userImage) {
            return (<img onClick={onProfileClick} src={userImage} title={username} />);
        }

        return (
            <div className={navigationStyles.noUserImage} onClick={onProfileClick}>
                <Icon name="fa-user" />
            </div>
        );
    }

    handlePinClick = () => {
        const {onPinClick} = this.props;

        if (onPinClick) {
            onPinClick();
        }
    };

    renderAppVersion() {
        const {
            title,
            appVersion,
            appVersionLink,
        } = this.props;

        if (!appVersion) {
            return null;
        }

        if (!appVersionLink) {
            return <div>{title} ({appVersion})</div>;
        }

        return <div>{title} (<a href={appVersionLink} rel="noopener noreferrer" target="_blank">{appVersion}</a>)</div>;
    }

    render() {
        const {
            title,
            username,
            onLogoutClick,
            onProfileClick,
            suluVersion,
            suluVersionLink,
            onPinClick,
        } = this.props;

        return (
            <div className={navigationStyles.navigation}>
                <div className={navigationStyles.header}>
                    <div className={navigationStyles.headerContent}>
                        <Icon className={navigationStyles.headerIcon} name="su-sulu" />
                        <span className={navigationStyles.headerTitle}>{title}</span>
                    </div>
                </div>
                <div className={navigationStyles.user}>
                    <div className={navigationStyles.userContent}>
                        {this.renderUserImage()}
                        <div className={navigationStyles.userProfile}>
                            <span onClick={onProfileClick}>{username}</span>
                            <button onClick={onLogoutClick}><Icon name="su-exit" />Log out</button>
                        </div>
                    </div>
                </div>
                <div className={navigationStyles.items}>
                    {this.cloneChildren()}
                </div>
                <div className={navigationStyles.footer}>
                    {onPinClick &&
                        <div className={navigationStyles.pinContainer}>
                            <ButtonGroup>
                                <Button className={navigationStyles.pin} onClick={this.handlePinClick}>
                                    <Icon name="fa-thumb-tack" />
                                </Button>
                            </ButtonGroup>
                        </div>
                    }
                    <div className={navigationStyles.versions}>
                        {this.renderAppVersion()}
                        <div>
                            Sulu (<a href={suluVersionLink} rel="noopener noreferrer" target="_blank">{suluVersion}</a>)
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
