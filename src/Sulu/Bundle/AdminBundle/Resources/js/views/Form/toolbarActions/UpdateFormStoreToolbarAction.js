// @flow
import {action, computed, observable} from 'mobx';
import React from 'react';
import symfonyRouting from 'fos-jsrouting/router';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import {Requester} from '../../../services';
import {translate} from '../../../utils';
import FormContainer, {memoryFormStoreFactory} from '../../../containers/Form';
import Router from '../../../services/Router';
import Form from '../Form';
import {ResourceStore} from '../../../stores';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {ResourceFormStore} from '../../../containers';
import type {FormStoreInterface} from '../../../containers/Form';

export default class UpdateFormStoreToolbarAction extends AbstractFormToolbarAction {
    @observable loading = false;
    @observable showDialog = false;
    formStore: FormStoreInterface;

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: { [key: string]: mixed },
        parentResourceStore: ResourceStore
    ) {
        super(
            resourceFormStore,
            form,
            router,
            locales,
            options,
            parentResourceStore
        );

        // Required options validation
        const requiredOptions = [
            'icon',
            'route',
            'contentExpressions',
            'dialogKey',
            'dialogTitle',
            'dialogDescription',
        ];

        const missingOptions = requiredOptions.filter((key) => !options[key]);
        if (missingOptions.length > 0) {
            throw new Error(`Missing required options: ${missingOptions.join(', ')}`);
        }

        // Validate content expressions
        if (!Array.isArray(options.contentExpressions)) {
            throw new Error('contentExpressions must be an array of objects with get and path properties');
        }
    }

    @computed get label() {
        const {
            label,
        } = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return label;
    }

    @computed get icon() {
        const {
            icon,
        } = this.options;

        if (typeof icon !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return icon;
    }

    @computed get formKey() {
        const {
            formKey,
        } = this.options;

        if (undefined === formKey) {
            return undefined;
        }

        if (typeof formKey !== 'string') {
            throw new Error('The "formKey" option must be a string value!');
        }

        return formKey;
    }

    @computed get dialogCancelText() {
        const {
            dialogCancelText,
        } = this.options;

        if (typeof dialogCancelText !== 'string') {
            throw new Error('The "dialogCancelText" option must be a string value!');
        }

        return dialogCancelText;
    }

    @computed get dialogKey() {
        const {
            dialogKey,
        } = this.options;

        if (typeof dialogKey !== 'string') {
            throw new Error('The "dialogKey" option must be a string value!');
        }

        return dialogKey;
    }

    @computed get dialogOkText() {
        const {
            dialogOkText,
        } = this.options;

        if (typeof dialogOkText !== 'string') {
            throw new Error('The "dialogOkText" option must be a string value!');
        }

        return dialogOkText;
    }

    @computed get dialogTitle() {
        const {
            dialogTitle,
        } = this.options;

        if (typeof dialogTitle !== 'string') {
            throw new Error('The "dialogTitle" option must be a string value!');
        }

        return dialogTitle;
    }

    @computed get dialogDescription() {
        const {
            dialogDescription,
        } = this.options;

        if (typeof dialogDescription !== 'string') {
            throw new Error('The "dialogDescription" option must be a string value!');
        }

        return dialogDescription;
    }

    @computed get contentExpressions(): Array<{ get: string, path: string, property: string }> {
        const {
            contentExpressions,
        } = this.options;

        if (!Array.isArray(contentExpressions)) {
            throw new Error('The "contentExpressions" option must be an array value!');
        }

        // Use Flow's type casting syntax
        return ((contentExpressions: any): Array<{ get: string, path: string, property: string }>);
    }

    getToolbarItemConfig() {
        return {
            type: 'button',
            label: this.label,
            icon: this.icon,
            onClick: this.handleClick,
            loading: this.loading,
        };
    }

    async evaluateJexl(expression: string, context: any) {
        return await jexl.eval(expression, context);
    }

    async getCurrentContent() {
        const context = {
            ...this.resourceFormStore.data,
        };

        const content = {};
        for (const expr of this.contentExpressions) {
            if (expr.get) {
                content[expr.property] = await this.evaluateJexl(expr.get, context);
            }
        }

        return content;
    }

    hasExistingContent(content: Object) {
        return Object.values(content).some((value) => value);
    }

    @action handleClick = async() => {
        const contentData = await this.getCurrentContent();

        if (this.hasExistingContent(contentData)) {
            if (this.formKey) {
                this.formStore = memoryFormStoreFactory.createFromFormKey(this.formKey);
            }

            this.openDialog();
        } else {
            this.fetchData();
        }
    };

    handleConfirm = () => {
        this.fetchData();
    };

    @action fetchData = async() => {
        const {
            locale,
            data: {
                id,
            },
        } = this.resourceFormStore;

        this.loading = true;

        const url = symfonyRouting.generate(this.options.route, {
            id,
            locale: locale?.get(),
            ...(this.options.routeParams || {}),
        });

        const content = await this.getCurrentContent();

        Requester.post(url, {
            content,
            data: this.formStore?.data || {},
        }).then(action((response: Object) => {
            this.form.showSuccessSnackbar();
            void this.changeContent(response);
            this.loading = false;
            this.closeDialog();
        })).catch(action(async(error) => {
            this.closeDialog();
            this.loading = false;

            const data = await error.json();
            this.setError(data.messageKey);
        }));
    };

    @action setError = (messageKey: string) => {
        this.form.errors = [...this.form.errors, translate(messageKey)];
    };

    getNode() {
        return (
            <Dialog
                cancelText={this.dialogCancelText || translate('sulu_admin.cancel')}
                confirmDisabled={this.loading || (this.formStore && !this.formStore.validate())}
                confirmLoading={this.loading}
                confirmText={this.dialogOkText || translate('sulu_admin.ok')}
                key={this.dialogKey}
                onCancel={this.handleDialogClose}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                title={this.dialogTitle}
            >
                {this.dialogDescription}

                {this.formStore && (
                    <FormContainer
                        onSubmit={this.handleConfirm}
                        store={this.formStore}
                    />
                )}
            </Dialog>
        );
    }

    @action changeContent = async(response: Object) => {
        for (const expr of this.contentExpressions) {
            const value = response[expr.property];

            if (expr.path) {
                this.resourceFormStore.change(expr.path, value);
            }
        }
    };

    handleDialogClose = () => {
        this.closeDialog();
    };

    @action closeDialog = () => {
        this.showDialog = false;
    };

    @action openDialog = () => {
        this.showDialog = true;
    };
}
