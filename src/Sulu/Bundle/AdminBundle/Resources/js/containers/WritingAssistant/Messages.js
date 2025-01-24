// @flow
import React, {Component} from 'react';
import {observer} from 'mobx-react';
import Message from './Message';
import Loader from './Loader';
import messagesStyles from './messages.scss';
import type {MessageType} from './types';

type Props = {|
    isLoading: boolean,
    loader: ?{
        commandTitle: string,
        expert: string,
    },
    locale: string,
    messages: Array<MessageType>,
    onCopy: (text: string) => void,
    onInsert: (text: string) => void,
    onMessageClicked: (index: number) => void,
    onRetry: (prompt: string, title: string) => void,
|};

/**
 * @internal
 */
@observer
class Messages extends Component<Props> {
    handleMessageCopyClicked = (text: string) => {
        const {onCopy} = this.props;
        onCopy(text);
    };

    handleMessageRetryClicked = (index: number) => {
        const {onRetry, messages} = this.props;

        onRetry(messages[index].command || '', messages[index].title || '');
    };

    handleMessageInsertClicked = (text: string) => {
        const {onInsert} = this.props;
        onInsert(text);
    };

    handleMessageClicked = (index: number) => {
        const {onMessageClicked} = this.props;
        onMessageClicked(index);
    };

    renderMessages = (messages: Array<MessageType>, offset: number = 0) => {
        // $FlowFixMe
        return messages.map((message: MessageType, index: number) => (
            <Message
                collapsed={index > 0 ? message.collapsed : false}
                command={message.command || ''}
                displayActions={message.displayActions || false}
                expert={message.expert}
                index={index + offset}
                isLoading={this.props.isLoading}
                key={index}
                locale={this.props.locale}
                onClick={index > 0 ? this.handleMessageClicked : undefined}
                onCopy={this.handleMessageCopyClicked}
                onInsert={this.handleMessageInsertClicked}
                onRetry={this.handleMessageRetryClicked}
                text={message.text}
                title={message.title || message.command}
                type={message.type}
            />
        ));
    };

    render() {
        const {
            messages,
            loader,
        } = this.props;

        // $FlowFixMe
        const loaderNode = loader ? <Loader {...loader} /> : undefined;

        return (
            <div className={messagesStyles.messages}>
                {this.renderMessages([messages[0]])}
                {messages.length > 1 || loader ? <hr className={messagesStyles.messageDivider} /> : undefined}
                {loaderNode}
                {this.renderMessages(messages.slice(1), 1)}
            </div>
        );
    }
}

export default Messages;
