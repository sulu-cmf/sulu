// @flow

import React, {Component} from 'react';
import classNames from 'classnames';
import WritingAssistantIcon from './icons/WritingAssistantIcon';
import TranslateIcon from './icons/TranslateIcon';
import styles from './featureBadge.scss';

type Props = {|
    messages: {
        translate: string,
        writingAssistant: string,
    },
    onTranslateClick?: () => void,
    onWritingAssistantClick?: () => void,
    skin: 'white' | 'gray',
|};

/**
 * @internal
 */
export default class FeatureBadge extends Component<Props> {
    handleClick = (event: MouseEvent) => {
        event.stopPropagation();
    };

    handleWritingAssistantClick = () => {
        const {onWritingAssistantClick} = this.props;

        if (onWritingAssistantClick) {
            onWritingAssistantClick();
        }
    };

    handleTranslateClick = () => {
        const {onTranslateClick} = this.props;

        if (onTranslateClick) {
            onTranslateClick();
        }
    };

    render() {
        const {
            messages: {
                writingAssistant: writingAssistantMessage,
                translate: translateMessage,
            },
            onWritingAssistantClick,
            onTranslateClick,
            skin,
        } = this.props;

        const className = classNames(styles.content, styles['content' + skin.charAt(0).toUpperCase() + skin.slice(1)]);

        return (
            <div
                className={styles.container}
                onClick={this.handleClick}
                role="button"
            >
                <div className={className}>
                    {onWritingAssistantClick && (
                        <button
                            className={styles.iconButton}
                            onClick={this.handleWritingAssistantClick}
                            title={writingAssistantMessage}
                            type="button"
                        >
                            <WritingAssistantIcon />
                        </button>
                    )}
                    {onWritingAssistantClick && onTranslateClick && <div className={styles.divider}></div>}
                    {onTranslateClick && (
                        <button
                            className={styles.iconButton}
                            onClick={this.handleTranslateClick}
                            title={translateMessage}
                            type="button"
                        >
                            <TranslateIcon />
                        </button>
                    )}
                </div>
            </div>
        );
    }
}
