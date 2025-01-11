// @flow
import React, {Component} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Input, SingleSelect, DropdownButton} from '../../components';
import {translate} from '../../utils';
import promptInputStyles from './prompt-input.scss';

type Props = {|
    experts: {
        name: string,
        text: string,
        type: 'text',
    } | {
        handleClick?: (string) => void,
        name: string,
        options: Array<{ id: string, name: string }>,
        selected: string,
        type: 'select',
    },
    isLoading: boolean,
    onAddMessage: (text: string) => Promise<void>,
    predefinedPrompts: ?{
        handleClick: (index: number) => void,
        label: string,
        options: Array<{
            id: number,
            name: string,
        }>,
    },
|};

@observer
class PromptInput extends Component<Props> {
    @observable messageInput: string;

    constructor(props) {
        super(props);
        this.messageInput = '';
    }

    @action handleInputChange = (message) => {
        this.messageInput = message;
    };

    @action handleSendMessage = () => {
        const {onAddMessage} = this.props;

        const messageInput = this.messageInput.trim();
        this.messageInput = '';
        if (messageInput !== '') {
            void onAddMessage(messageInput);
        }
    };

    handleKeyDown = (key: string) => {
        if (key === 'Enter') {
            this.handleSendMessage();
        }
    };

    renderPredefinedPrompts = () => {
        const {predefinedPrompts, isLoading} = this.props;
        if (!predefinedPrompts) {
            return null;
        }

        return (
            <div className={promptInputStyles.predefinedPromptsDropdown}>
                <DropdownButton icon="fa-terminal" label={predefinedPrompts.label} skin="secondary">
                    {predefinedPrompts.options.map((option) => (
                        <DropdownButton.Item
                            disabled={isLoading}
                            key={option.id}
                            onClick={predefinedPrompts.handleClick}
                            value={option.id}
                        >
                            {option.name}
                        </DropdownButton.Item>
                    ))}
                </DropdownButton>
            </div>
        );
    };

    render() {
        const {experts, predefinedPrompts} = this.props;

        return (
            <div className={promptInputStyles.inputContainer}>
                <div className={promptInputStyles.predefinedPrompts}>
                    {predefinedPrompts.length > 0 && (
                        <div>
                            {experts.type === 'select' ? (
                                <div className={promptInputStyles.expertSelect}>
                                    <SingleSelect onChange={experts.handleClick} value={this.props.experts.selected}>
                                        {experts.options.map((option) => (
                                            <SingleSelect.Option key={option.id} value={option.id}>
                                                {option.name}
                                            </SingleSelect.Option>
                                        ))}
                                    </SingleSelect>
                                </div>
                            ) : (
                                <span className={promptInputStyles.singleExpert}>{experts.text}</span>
                            )}
                        </div>
                    )}
                    {this.renderPredefinedPrompts()}
                </div>
                <div className={promptInputStyles.input}>
                    {predefinedPrompts.length === 0 && (
                        <div className={promptInputStyles.singleExpertContainer}>
                            {experts.type === 'select' ? (
                                <div className={promptInputStyles.expertSelect}>
                                    <SingleSelect onChange={experts.handleClick} value={this.props.experts.selected}>
                                        {experts.options.map((option) => (
                                            <SingleSelect.Option key={option.id} value={option.id}>
                                                {option.name}
                                            </SingleSelect.Option>
                                        ))}
                                    </SingleSelect>
                                </div>
                            ) : (
                                <span className={promptInputStyles.singleExpert}>{experts.text}</span>
                            )}
                        </div>
                    )}
                    <Input
                        onChange={this.handleInputChange}
                        onKeyPress={this.handleKeyDown}
                        placeholder={translate('sulu_ai.add_message')}
                        type="text"
                        value={this.messageInput}
                    />
                    <Button
                        disabled={(this.messageInput?.trim() ?? '') === ''}
                        onClick={this.handleSendMessage}
                        skin="primary"
                    >{translate('sulu_ai.send')}</Button>
                </div>
            </div>
        );
    }
}

export default PromptInput;
