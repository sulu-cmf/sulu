// @flow

import React, {Component} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import symfonyRouting from 'fos-jsrouting/router';
import {translate} from '../../utils';
import {FormInspector} from '../../containers';
import WritingAssistant from '../WritingAssistant';
import Translator from '../Translator';
import FeatureBadge from './FeatureBadge';
import ActionOverlay from './ActionOverlay';
import type {ExpertType} from '../WritingAssistant/types';

type Props = {|
    feedback: ?{
        enabled: boolean,
        formKey: string,
        route: string,
    },
    translation: {
        enabled: boolean,
        route: string,
        sourceLanguages: Array<{|
            label: string,
            locale: string,
        |}>,
        targetLanguages: Array<{|
            label: string,
            locale: string,
        |}>,
    },
    writingAssistant: {
        enabled: boolean,
        experts: {
            [string]: ExpertType,
        },
        route: string,
    },
|};

@observer
export default class AiApplication extends Component<Props> {
    @observable selectedComponent: {
        formInspector: FormInspector,
        getValue: () => string,
        isInsideBlock: boolean,
        name: string,
        schemaType: string,
        setValue: (value: string) => void,
    };
    @observable selectedText: string;
    @observable selectedRect: ClientRect;
    @observable selectedElement: HTMLElement;
    @observable writingAssistantOpen: boolean = false;
    @observable translateOpen: boolean = false;
    @observable hasFocus: boolean = false;
    @observable writingAssistantIdentifier: ?number = undefined;
    @observable translateIdentifier: ?number = undefined;

    componentDidMount() {
        ['scroll', 'resize'].forEach((eventName) => {
            window.addEventListener(eventName, this.handleScrollResize, true);
        });

        document.addEventListener('sulu.focus', this.handleSuluFocus);
        document.addEventListener('click', this.handleGlobalClick);
    }

    componentWillUnmount() {
        ['scroll', 'resize'].forEach((eventName) => {
            window.removeEventListener(eventName, this.handleScrollResize, true);
        });

        document.removeEventListener('sulu.focus', this.handleSuluFocus);
        document.removeEventListener('click', this.handleGlobalClick);
    }

    @action handleScrollResize = () => {
        if (this.selectedElement && this.selectedElement.parentElement) {
            this.selectedRect = this.selectedElement.parentElement.getBoundingClientRect();
        }
    };

    @action handleSuluFocus = (event: Event) => {
        if (this.translateOpen || this.writingAssistantOpen || !(event.target instanceof HTMLElement)) {
            return;
        }

        this.selectedElement = event.target;
        if (this.selectedElement.parentElement) {
            this.selectedRect = this.selectedElement.parentElement.getBoundingClientRect();
        }

        const detail: {
            formInspector: FormInspector,
            getValue: () => string,
            schemaPath: string,
            schemaType: string,
            setValue: (value: string) => void,
        // $FlowFixMe
        } = event.detail;
        if (!detail) {
            return;
        }

        this.selectedComponent = {
            formInspector: detail.formInspector,
            getValue: detail.getValue,
            isInsideBlock: this.isInsideBlock(detail.formInspector, detail.schemaPath),
            name: detail.schemaPath.split('/').slice(0, -1)[0],
            schemaType: detail.schemaType,
            setValue: detail.setValue,
        };
        this.selectedText = this.selectedComponent.getValue();
        this.setFocus(true);
    };

    @action handleGlobalClick = (event: Event) => {
        if ((event.target instanceof HTMLElement) && !this.isRelevantElement(event.target)) {
            this.hasFocus = false;
        }
    };

    @action setFocus = (focused: boolean) => {
        this.hasFocus = focused;
    };

    isRelevantElement(element: HTMLElement) {
        return element.matches('input, textarea, [contenteditable]')
            || element.closest('[contenteditable]');
    }

    isInsideBlock(formInspector: FormInspector, schemaPath: string) {
        if (schemaPath.startsWith('/ext')) {
            return false;
        }

        const parts = schemaPath.split('/');

        for (let i = 2; i < parts.length; i++) {
            const path = '/' + parts.slice(1, i).join('/');
            const schema = formInspector.getSchemaEntryByPath(path);
            if (schema?.type === 'block') {
                return true;
            }
        }

        return false;
    }

    moveCursorToEnd = (element: HTMLInputElement) => {
        element.focus();
        if (typeof element.selectionStart === 'number') {
            // For input and textarea elements
            element.selectionStart = element.selectionEnd = element.value.length;
        } else if (window.getSelection && document.createRange) {
            // For contenteditable elements
            const range = document.createRange();
            range.selectNodeContents(element);
            range.collapse(false);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    };

    @action handleWritingAssistantClose = () => {
        this.selectedText = this.selectedComponent.getValue();
        this.writingAssistantOpen = false;
        this.setFocus(false);
    };

    @action handleWritingAssistantConfirm = (optimizedText: string) => {
        this.selectedComponent.setValue(optimizedText);

        if (this.selectedElement instanceof HTMLInputElement) {
            this.moveCursorToEnd(this.selectedElement);
        }
        this.writingAssistantOpen = false;
        this.setFocus(true);
    };

    @action handleTranslateClose = () => {
        this.selectedText = this.selectedComponent.getValue();
        this.translateOpen = false;
        this.setFocus(false);
    };

    @action handleTranslateConfirm = (translatedText: string) => {
        this.selectedComponent.setValue(translatedText);

        if (this.selectedElement instanceof HTMLInputElement) {
            this.moveCursorToEnd(this.selectedElement);
        }
        this.translateOpen = false;
        this.setFocus(true);
    };

    @computed get position() {
        return {
            position: 'absolute',
            bottom: this.selectedRect ? (window.innerHeight - this.selectedRect.top + this.delta) + 'px' : 0,
            right: this.selectedRect ? (window.innerWidth - this.selectedRect.right) + 'px' : 0,
        };
    }

    @computed get delta() {
        if (this.selectedComponent?.schemaType === 'text_line') {
            return 5;
        }

        if (this.selectedComponent?.schemaType === 'text_area') {
            return 5;
        }

        if (this.selectedComponent?.schemaType === 'text_editor') {
            return 50;
        }

        return 5;
    }

    @action handleWritingAssistantOpen = () => {
        this.selectedText = this.selectedComponent.getValue();

        this.writingAssistantOpen = true;
        this.writingAssistantIdentifier = Math.floor(Math.random() * 10000000);
    };

    @action handleTranslateOpen = () => {
        this.selectedText = this.selectedComponent.getValue();

        this.translateOpen = true;
        this.translateIdentifier = Math.floor(Math.random() * 10000000);
    };

    @computed get writingAssistantUrl() {
        return symfonyRouting.generate(this.props.writingAssistant.route, {
            chatId: this.writingAssistantIdentifier,
        });
    }

    @computed get translationUrl() {
        return symfonyRouting.generate(this.props.translation.route, {
            translateId: this.translateIdentifier,
        });
    }

    @computed get actionUrl() {
        if (!this.props.feedback) {
            return undefined;
        }

        return symfonyRouting.generate(this.props.feedback.route);
    }

    render() {
        const {
            writingAssistant: {
                enabled: writingAssistantEnabled,
            },
            translation: {
                enabled: translationEnabled,
            },
        } = this.props;

        if (!this.hasFocus
            && !this.writingAssistantOpen
            && !this.translateOpen
        ) {
            return null;
        }

        const locale = this.selectedComponent.formInspector.locale?.get().toLowerCase();
        if (!locale) {
            return null;
        }

        const schemaType = this.selectedComponent?.schemaType || 'text_line';
        if (schemaType !== 'text_line' && schemaType !== 'text_area' && schemaType !== 'text_editor') {
            return null;
        }

        return (
            <div style={this.position}>
                {!this.writingAssistantOpen && !this.translateOpen && (
                    <FeatureBadge
                        messages={{
                            translate: translate('sulu_admin.translator_title'),
                            writingAssistant: translate('sulu_admin.writing_assistant_title'),
                        }}
                        onTranslateClick={translationEnabled ? this.handleTranslateOpen : undefined}
                        onWritingAssistantClick={writingAssistantEnabled ? this.handleWritingAssistantOpen : undefined}
                        skin={this.selectedComponent.isInsideBlock ? 'gray' : 'white'}
                    />
                )}
                {this.writingAssistantOpen && writingAssistantEnabled && (
                    <WritingAssistant
                        action={this.props.feedback?.enabled ? ActionOverlay : undefined}
                        actionProps={{
                            formKey: this.props.feedback?.formKey,
                            url: this.actionUrl,
                        }}
                        configuration={this.props.writingAssistant}
                        locale={locale}
                        messages={{
                            addMessage: translate('sulu_admin.writing_assistant_prompt_placeholder'),
                            copiedToClipboard: translate('sulu_admin.writing_assistant_copied_to_clipboard'),
                            initialMessage: translate('sulu_admin.writing_assistant_initial_message'),
                            predefinedPrompts: translate('sulu_admin.writing_assistant_predefined_prompts'),
                            send: translate('sulu_admin.writing_assistant_send'),
                            writingAssistant: translate('sulu_admin.writing_assistant_title'),
                        }}
                        onConfirm={this.handleWritingAssistantConfirm}
                        onDialogClose={this.handleWritingAssistantClose}
                        type={schemaType}
                        url={this.writingAssistantUrl}
                        value={this.selectedText}
                    />
                )}
                {this.translateOpen && translationEnabled && (
                    <Translator
                        action={this.props.feedback?.enabled ? ActionOverlay : undefined}
                        actionProps={{
                            formKey: this.props.feedback?.formKey,
                            url: this.actionUrl,
                        }}
                        locale={locale}
                        messages={{
                            title: translate('sulu_admin.translator_title'),
                            insert: translate('sulu_admin.translator_insert'),
                            detected: translate('sulu_admin.translator_detected'),
                            errorTranslatingText: translate('sulu_admin.translator_error'),
                        }}
                        onConfirm={this.handleTranslateConfirm}
                        onDialogClose={this.handleTranslateClose}
                        sourceLanguages={this.props.translation.sourceLanguages}
                        targetLanguages={this.props.translation.targetLanguages}
                        type={schemaType}
                        url={this.translationUrl}
                        value={this.selectedText}
                    />
                )}
            </div>
        );
    }
}
