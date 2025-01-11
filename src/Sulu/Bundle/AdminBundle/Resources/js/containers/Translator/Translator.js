// @flow

import React from 'react';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {action, observable, toJS} from 'mobx';
import {Overlay, SingleSelect} from '../../components';
import {Requester} from '../../services';
import translatorStyles from './translator.scss';
import Input from './Input';

type Props = {|
    action?: React$ComponentType<{|
        context: Object,
        source: string,
    |}>,
    locale: string,
    messages: {|
        detected: string,
        errorTranslatingText: string,
        insert: string,
        title: string,
    |},
    onConfirm: (text: string) => void,
    onDialogClose: () => void,
    sourceLanguages: Array<{|
        label: string,
        locale: string,
    |}>,
    targetLanguages: Array<{|
        label: string,
        locale: string,
    |}>,
    type: 'text_line' | 'text_area' | 'text_editor',
    url: string,
    value?: string,
|};

/**
 * @internal
 */
@observer
export default class Translator extends React.Component<Props> {
    @observable snackbarMessage: ?{ message: string, type: 'error' } = undefined;
    @observable loading = false;
    @observable sourceText = '';
    @observable targetText = '';
    @observable sourceLanguage = undefined;
    @observable sourceSelectedOnce = false;
    @observable targetLanguage = undefined;
    @observable lastResponse = undefined;

    @action handleClose = () => {
        const {onDialogClose} = this.props;

        onDialogClose();
    };

    @action handleConfirm = () => {
        const {
            onConfirm,
        } = this.props;

        // We have to stop the propagation of the event to prevent the focus lose of the input / editor field
        // $FlowFixMe
        event.stopPropagation();

        onConfirm(this.targetText);
    };

    @action handleSnackbarCloseClick = () => {
        this.snackbarMessage = undefined;
    };

    @action componentDidMount() {
        this.targetLanguage = this.props.locale;
        this.sourceText = this.props.value;

        this.translateText(this.sourceText);
    }

    @action handleSourceTextChanged = (text: string) => {
        this.sourceText = text;

        this.translateText(text);
    };

    translateText = debounce(action(() => {
        const {
            url,
            messages: {
                errorTranslatingText: errorTranslatingTextMessage,
            },
        } = this.props;

        this.loading = true;
        this.lastResponse = undefined;

        return Requester.post(
            url,
            {
                text: this.sourceText,
                sourceLanguage: this.sourceLanguage,
                targetLanguage: this.targetLanguage,
            }
        ).then(action((data: {
            response: {
                sourceLanguage: string,
                targetLanguage: string,
                text: string,
            },
        }) => {
            this.loading = false;
            this.targetText = data.response.text;
            this.lastResponse = data;

            this.targetLanguage = data.response.targetLanguage.toLowerCase();
            this.sourceLanguage = data.response.sourceLanguage.toLowerCase();

            return data;
        })).catch(action((error) => {
            this.loading = false;
            this.lastResponse = {error};

            this.snackbarMessage = {
                message: errorTranslatingTextMessage,
                type: 'error',
            };
        }));
    }), 500);

    @action handleSourceLanguageChanged = (locale: string) => {
        this.sourceLanguage = locale;
        this.sourceSelectedOnce = true;

        this.translateText(this.sourceText);
    };

    @action handleTargetLanguageChanged = (locale: string) => {
        this.targetLanguage = locale;

        this.translateText(this.sourceText);
    };

    render() {
        const {
            type,
            sourceLanguages,
            targetLanguages,
            action: Action,
            messages: {
                title: titleMessage,
                insert: insertMessage,
                detected: detectedMessage,
            },
        } = this.props;

        const actionNode = Action ? (
            <Action
                context={toJS(this.lastResponse)}
                source="translator"
            />
        ) : <React.Fragment />;

        return (
            <Overlay
                confirmDisabled={this.targetText === ''}
                confirmLoading={this.loading}
                confirmText={insertMessage}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={true}
                size="small"
                snackbarMessage={this.snackbarMessage?.message}
                snackbarType={this.snackbarMessage?.type}
                title={titleMessage}
            >
                {actionNode}

                <div className={translatorStyles.translator}>
                    <div className={translatorStyles.column}>
                        <div className={translatorStyles.select}>
                            <SingleSelect
                                onChange={this.handleSourceLanguageChanged}
                                skin="flat"
                                value={this.sourceLanguage}
                            >
                                {sourceLanguages.map((option) => {
                                    const isDetected = option.locale.toLowerCase() === this.sourceLanguage
                                        && !this.sourceSelectedOnce;

                                    return (
                                        <SingleSelect.Option key={option.locale} value={option.locale.toLowerCase()}>
                                            {option.label}
                                            {isDetected && ' (' + detectedMessage + ')'}
                                        </SingleSelect.Option>
                                    );
                                })}
                            </SingleSelect>
                        </div>
                        <Input
                            onChange={this.handleSourceTextChanged}
                            text={this.sourceText || ''}
                            type={type}
                        />
                    </div>
                    <div className={translatorStyles.column}>
                        <div className={translatorStyles.select}>
                            <SingleSelect
                                onChange={this.handleTargetLanguageChanged}
                                skin="flat"
                                value={this.targetLanguage}
                            >
                                {targetLanguages.map((option) => (
                                    <SingleSelect.Option key={option.locale} value={option.locale.toLowerCase()}>
                                        {option.label}
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </div>
                        <Input
                            text={this.targetText}
                            type={type}
                        />
                    </div>
                </div>
            </Overlay>
        );
    }
}
