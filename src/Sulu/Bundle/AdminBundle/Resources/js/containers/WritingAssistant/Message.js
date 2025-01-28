// @flow

import React, {Component, Fragment} from 'react';
import {observable} from 'mobx';
import {Button} from '../../components';
import {TextEditor} from '../../containers';
import {translate} from '../../utils';
import messageStyles from './message.scss';

type Props = {|
    collapsed: boolean,
    command: string,
    displayActions: boolean,
    expert: ?string,
    index: number,
    isLoading: boolean,
    locale: string,
    onClick?: (index: number) => void,
    onCopy: (text: string) => void,
    onInsert: (text: string) => void,
    onRetry: (index: number) => void,
    text: string,
    title?: string,
    type: 'text_line' | 'text_area' | 'text_editor',
|};

const MAX_LENGTH = 80;

/**
 * @internal
 */
class Message extends Component<Props> {
    handleOnRetry = () => {
        const {
            index,
            onRetry,
        } = this.props;

        onRetry(index);
    };

    handleOnCopy = () => {
        const {
            text,
            onCopy,
        } = this.props;

        onCopy(text);
    };

    handleOnInsert = () => {
        const {
            text,
            onInsert,
        } = this.props;

        onInsert(text);
    };

    handleOnClick = () => {
        const {
            index,
            onClick,
        } = this.props;
        if (!onClick) {
            return;
        }

        onClick(index);
    };

    trimText = (text: string) => {
        // strip html tags
        text = text?.replace(/<\/?[^>]+(>|$)/g, '') ?? '';
        if (text.length <= 100) {
            return text;
        }

        const firstPartEnd = text.lastIndexOf(' ', 70);
        const firstPart = firstPartEnd !== -1 ? text.substring(0, firstPartEnd) : text.substring(0, 70);

        const lastPartStart = text.slice(-20).indexOf(' ');
        const lastPart = lastPartStart !== -1
            ? text.substring(text.length - 20 + lastPartStart + 1)
            : text.substring(text.length - 20);

        return `${firstPart} ... ${lastPart}`;
    };

    renderTextComponent = () => {
        const {
            type,
            text,
            collapsed,
            locale,
        } = this.props;

        if (collapsed) {
            return this.trimText(text);
        }

        if (type !== 'text_editor') {
            return text;
        }

        return (
            <div className={messageStyles.textEditor}>
                <TextEditor
                    adapter="ckeditor5"
                    disabled={true}
                    locale={observable.box(locale)}
                    onChange={this.handleTextEditorChange}
                    value={text}
                />
            </div>
        );
    };

    handleTextEditorChange = () => {
        // do nothing as text editor is always disabled
    };

    render() {
        const {
            title,
            text,
            expert,
            command,
            collapsed,
            isLoading,
            displayActions,
        } = this.props;

        if (text === '') {
            return null;
        }

        const commandTitle = title || command;

        return (
            <Fragment>
                {
                    commandTitle.substring(0, MAX_LENGTH) + ' ...' &&
                        <div className={messageStyles.command}>
                            {commandTitle}
                            {expert && <div className={messageStyles.expert}>{expert}</div>}
                        </div>
                }

                <div
                    className={messageStyles.message}
                    onClick={this.handleOnClick}
                    role="button"
                >
                    <div className={messageStyles.text}>
                        {this.renderTextComponent()}
                    </div>

                    {displayActions && !collapsed &&
                        <div className={messageStyles.actions}>
                            <Button
                                className={messageStyles.insertButton}
                                disabled={isLoading}
                                onClick={this.handleOnInsert}
                                size="small"
                                skin="secondary"
                            >{translate('sulu_admin.insert')}</Button>
                            <Button
                                disabled={isLoading}
                                icon="su-sync"
                                onClick={this.handleOnRetry}
                                size="small"
                                skin="icon"
                            />
                            <Button
                                disabled={isLoading}
                                icon="su-copy"
                                onClick={this.handleOnCopy}
                                size="small"
                                skin="icon"
                            />
                        </div>
                    }
                </div>
            </Fragment>
        );
    }
}

export default Message;
