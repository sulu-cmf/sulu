// @flow
import React from 'react';
import Icon from '../Icon';
import hintStyles from './hint.scss';

type Props = {
    icon: string,
    title: string,
};

export default class Hint extends React.Component<Props> {
    render() {
        const {
            icon,
            title,
        } = this.props;

        return (
            <div className={hintStyles.hint}>
                <div className={hintStyles.hintIcon}>
                    <Icon name={icon} />
                </div>
                {title}
            </div>
        );
    }
}
