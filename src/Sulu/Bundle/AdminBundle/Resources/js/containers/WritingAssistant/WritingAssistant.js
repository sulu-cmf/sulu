// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed, toJS} from 'mobx';
import {Requester} from '../../services';
import {Overlay} from '../../components';
import writingAssistantStyles from './writingAssistant.scss';
import Messages from './Messages';
import PromptInput from './PromptInput';
import type {ExpertType, MessageType} from './types';

type Props = {|
    action?: React$ComponentType<Object>,
    actionProps?: Object,
    configuration: {
        experts: {
            [string]: ExpertType,
        },
    },
    locale: string,
    messages: {
        addMessage: string,
        copiedToClipboard: string,
        initialMessage: string,
        predefinedPrompts: string,
        send: string,
        writingAssistant: string,
    },
    onConfirm: (text: string) => void,
    onDialogClose: () => void,
    type: 'text_line' | 'text_area' | 'text_editor',
    url: string,
    value?: string,
|};

/**
 * @internal
 */
@observer
export default class WritingAssistant extends React.Component<Props> {
    @observable messages: Array<MessageType> = [];
    @observable loader: ?{
        commandTitle: string,
        expert: string,
    } = undefined;
    @observable selectedExpert: string;
    @observable snackbarMessage = undefined;
    @observable lastResponse = undefined;
    @observable currentValue: string;

    constructor(props: Props) {
        super(props);

        this.selectedExpert = this.experts[0].uuid;
        // push initial message
        this.messages.push(
            {
                title: this.props.messages.initialMessage,
                text: props.value || '',
                type: props.type,
                collapsed: true,
                displayActions: false,
            }
        );

        this.currentValue = props.value || '';
    }

    @action handleAddMessage = async(prompt: string, title: ?string) => {
        const {type} = this.props;
        const result = await this.optimizeText(prompt, title);
        this.currentValue = result.text;

        this.addMessage(
            {
                command: prompt,
                title: title || prompt,
                expert: this.props.configuration.experts[this.selectedExpert].name,
                text: result.text,
                type,
                collapsed: false,
                displayActions: true,
            }
        );
    };

    @action addMessage = (message: MessageType) => {
        this.messages = [this.messages[0], message, ...this.messages.slice(1)];
        if (this.messages.length >= 3) {
            this.messages[2].collapsed = true;
        }
    };

    @action optimizeText = async(prompt: string, title: ?string) => {
        const {
            locale,
            url,
        } = this.props;

        this.loader = {
            commandTitle: title ?? prompt,
            expert: this.props.configuration.experts[this.selectedExpert].name,
        };
        return Requester.post(
            url,
            {
                text: this.currentValue,
                message: prompt,
                expertUuid: this.selectedExpert,
                locale,
            }
        ).then(action((data) => {
            this.loader = undefined;
            this.lastResponse = data;
            return data.response;
        })).catch(action((error) => {
            this.loader = undefined;
            this.lastResponse = {error};
        }));
    };

    handlePredefinedPromptButtonClick = (action: {name: string, prompt: string}) => {
        void this.handleAddMessage(action.prompt, action.name);
    };

    handlePredefinedPromptSelectClick = (index: number) => {
        const {configuration} = this.props;
        const predefinedPrompts = configuration.experts[this.selectedExpert].options.predefinedPrompts || [];

        this.handlePredefinedPromptButtonClick({
            name: predefinedPrompts[index].name,
            prompt: predefinedPrompts[index].prompt,
        });
    };

    @action handleExpertSelect = (expert: string) => {
        this.selectedExpert = expert;
    };

    @action handleOnRetry = (prompt: string, title: string) => {
        void this.handleAddMessage(prompt, title);
    };

    @action handleOnMessageClicked = (index: number) => {
        this.messages[index].collapsed = !this.messages[index].collapsed;
    };

    @computed get experts(): Array<ExpertType> {
        // $FlowFixMe
        return (Object.values(this.props.configuration.experts): Array<ExpertType>) || [];
    }

    @computed get expertsButton() {
        if (this.experts === 1) {
            return {
                name: 'experts',
                type: 'text',
                text: this.experts[0].name,
            };
        }

        return {
            name: 'experts',
            type: 'select',
            selected: this.selectedExpert,
            options: this.experts.map((expert: ExpertType): { id: string, name: string } => {
                return {
                    id: expert.uuid,
                    name: expert.name,
                };
            }),
            handleClick: this.handleExpertSelect,
        };
    }

    @computed get predefinedPrompts() {
        const {
            configuration,
            messages: {
                predefinedPrompts: predefinedPromptsMessage,
            },
        } = this.props;

        const predefinedPrompts = configuration.experts[this.selectedExpert].options.predefinedPrompts || [];

        return predefinedPrompts.length > 1 ? {
            label: predefinedPromptsMessage,
            options: predefinedPrompts.map((predefinedPrompt, index) => ({
                id: index,
                name: predefinedPrompt.name,
            })) ?? [],
            handleClick: this.handlePredefinedPromptSelectClick,
        } : undefined;
    }

    @action handleDialogClose = () => {
        const {onDialogClose} = this.props;
        onDialogClose();
    };

    @action handleOnInsert = (text: string) => {
        const {
            onConfirm,
        } = this.props;

        // We have to stop the propagation of the event to prevent the focus lose of the input / editor field
        // $FlowFixMe
        event.stopPropagation();
        onConfirm(text);
    };

    @action handleOnCopy = (text: string) => {
        const {
            messages: {
                copiedToClipboard: copiedToClipboardMessage,
            },
        } = this.props;

        void navigator.clipboard.writeText(text);
        this.snackbarMessage = copiedToClipboardMessage;
    };

    @action handleSnackbarCloseClick = () => {
        this.snackbarMessage = undefined;
    };

    render() {
        const {
            action: Action,
            locale,
            messages: {
                writingAssistant: writingAssistantMessage,
                addMessage: addMessageMessage,
                send: sendMessage,
            },
        } = this.props;

        const actionNode = Action ? (
            <Action
                {...(this.props.actionProps || {})}
                context={toJS(this.lastResponse)}
                source="writing_assistant"
            />
        ) : <React.Fragment />;

        return (
            <Overlay
                onClose={this.handleDialogClose}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={true}
                size="small"
                snackbarMessage={this.snackbarMessage}
                snackbarType="success"
                title={writingAssistantMessage}
            >
                {actionNode}

                <div className={writingAssistantStyles.content}>
                    <div className={writingAssistantStyles.chat}>
                        <Messages
                            isLoading={!!this.loader}
                            loader={this.loader}
                            locale={locale}
                            messages={toJS(this.messages)}
                            onCopy={this.handleOnCopy}
                            onInsert={this.handleOnInsert}
                            onMessageClicked={this.handleOnMessageClicked}
                            onRetry={this.handleOnRetry}
                        />
                        <PromptInput
                            experts={this.expertsButton}
                            isLoading={!!this.loader}
                            messages={{
                                addMessage: addMessageMessage,
                                send: sendMessage,
                            }}
                            onAddMessage={this.handleAddMessage}
                            predefinedPrompts={this.predefinedPrompts}
                        />
                    </div>
                </div>
            </Overlay>
        );
    }
}
