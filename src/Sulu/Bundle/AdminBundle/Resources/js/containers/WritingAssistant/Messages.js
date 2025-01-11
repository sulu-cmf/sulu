// @flow
import React, {Component} from 'react';
import {observer} from 'mobx-react';
import Message from './Message';
import Loader from './Loader';
import messagesStyles from './messages.scss';
import type {MessageType} from './types';

type Props = {|
    loader: ?{
        commandTitle: string,
        expert: string,
    },
    messages: Array<MessageType>,
    onCopy: (text: string) => void,
    onInsert: (text: string) => void,
    onMessageClicked: (index: number) => void,
    onRetry: (prompt: string, title: string) => void,
|};

@observer
class Messages extends Component<Props> {
    handleMessageCopyClicked = (text: string) => {
        const {onCopy} = this.props;
        onCopy(text);
    };

    handleMessageRetryClicked = (index: number) => {
        const {onRetry, messages} = this.props;
        onRetry(messages[index].command, messages[index].title);
    };

    handleMessageInsertClicked = (text: string) => {
        const {onInsert} = this.props;
        onInsert(text);
    };

    handleMessageClicked = (index: number) => {
        const {onMessageClicked} = this.props;
        onMessageClicked(index);
    };

    renderMessages = (messages, offset = 0) => {
        return messages.map((message, index) => (
            <Message
                collapsed={index > 0 ? message.collapsed : null}
                command={message.command}
                displayActions={message.displayActions}
                expert={message.expert}
                index={index + offset}
                key={index}
                onClick={index > 0 ? this.handleMessageClicked : null}
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
        const {messages, loader} = this.props;

        return (
            <div className={messagesStyles.messages}>
                {this.renderMessages([messages[0]])}
                {messages.length > 1 || loader ? <hr className={messagesStyles.messageDivider} /> : null}
                {loader && <Loader {...loader} />}
                {this.renderMessages(messages.slice(1), 1)}
            </div>
        );
    }
}

export default Messages;
