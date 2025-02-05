// @flow
import {action, computed, observable} from 'mobx';
import React from 'react';
import symfonyRouting from 'fos-jsrouting/router';
import Dialog from '../../../components/Dialog';
import {Requester} from '../../../services';
import {translate} from '../../../utils';
import Router from '../../../services/Router';
import Form from '../Form';
import {ResourceStore} from '../../../stores';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {ResourceFormStore} from '../../../containers';

/**
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class ReloadFormStoreToolbarAction extends AbstractFormToolbarAction {
    @observable loading = false;
    @observable showDialog = false;

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
            'dialogKey',
            'dialogTitle',
            'dialogDescription',
        ];

        const missingOptions = requiredOptions.filter((key) => !options[key]);
        if (missingOptions.length > 0) {
            throw new Error(`Missing required options: ${missingOptions.join(', ')}`);
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
            throw new Error('The "icon" option must be a string value!');
        }

        return icon;
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

    @computed get dialogOkText() {
        const {
            dialogOkText,
        } = this.options;

        if (typeof dialogOkText !== 'string') {
            throw new Error('The "dialogOkText" option must be a string value!');
        }

        return dialogOkText;
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

    getToolbarItemConfig() {
        return {
            type: 'button',
            label: this.label,
            icon: this.icon,
            onClick: this.handleClick,
        };
    }

    @action handleClick = async() => {
        this.openDialog();
    };

    handleOnConfirm = () => {
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
        Requester.post(url).then(action(() => {
            this.form.showSuccessSnackbar();
            this.resourceFormStore.resourceStore.load();
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
                confirmLoading={this.loading}
                confirmText={this.dialogOkText || translate('sulu_admin.ok')}
                key={this.dialogKey}
                onCancel={this.handleDialogClose}
                onConfirm={this.handleOnConfirm}
                open={this.showDialog}
                title={this.dialogTitle}
            >
                {this.dialogDescription}
            </Dialog>
        );
    }

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
