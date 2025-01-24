// @flow
import React, {Component} from 'react';
import styles from './feedbackButton.scss';
import FeedbackIcon from './icons/ActionIcon';

type Props = {|
    messages: {
        title: string,
    },
    onClick: () => void,
|};

export default class ActionButton extends Component<Props> {
    render() {
        const {
            onClick,
            messages: {
                title: titleMessage,
            },
        } = this.props;

        return (
            <div className={styles.feedbackContainer}>
                <div className={styles.feedbackTab} onClick={onClick} role="button">
                    <span className={styles.feedbackIcon}>
                        <FeedbackIcon />
                    </span>
                    {titleMessage}
                </div>
            </div>
        );
    }
}
