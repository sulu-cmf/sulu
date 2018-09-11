// @flow
import classNames from 'classnames';
import {Portal} from 'react-portal';
import React from 'react';
import backdropStyles from './backdrop.scss';

type Props = {
    open: boolean,
    /** When set to false the backdrop renders transparent. */
    visible: boolean,
    /** If true, the backdrop gets rendered in the placed element and not in the body. */
    local: boolean,
    onClick?: () => void,
    fixed: boolean,
};

export default class Backdrop extends React.PureComponent<Props> {
    static defaultProps = {
        fixed: true,
        local: false,
        open: true,
        visible: true,
    };

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        const {
            open,
            visible,
            local,
            fixed,
        } = this.props;
        const backdropClass = classNames(
            backdropStyles.backdrop,
            {
                [backdropStyles.visible]: visible,
                [backdropStyles.fixed]: fixed,
            }
        );
        const backdrop = <div className={backdropClass} onClick={this.handleClick} />;

        if (!open) {
            return null;
        }

        if (local) {
            return backdrop;
        }

        return <Portal>{backdrop}</Portal>;
    }
}
