// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils';
import FormContainer, {ResourceFormStore, memoryFormStoreFactory} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Form from '../Form';
import copyLocaleActionStyles from './copyLocaleAction.scss';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {FormStoreInterface} from '../../../containers';

export default class CopyLocaleToolbarAction extends AbstractFormToolbarAction {
    @observable showCopyLocaleDialog = false;
    @observable selectedLocales: Array<string> = [];
    @observable copying: boolean = false;
    formStore: FormStoreInterface;

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed},
        parentResourceStore: ResourceStore
    ) {
        const {
            display_condition: displayCondition,
            visible_condition: visibleCondition,
        } = options;

        if (displayCondition) {
            // @deprecated
            log.warn(
                'The "display_condition" option is deprecated since version 2.0 and will be removed. ' +
                'Use the "visible_condition" option instead.'
            );

            if (!visibleCondition) {
                options.visible_condition = displayCondition;
            }
        }

        super(resourceFormStore, form, router, locales, options, parentResourceStore);

        if (locales) {
            this.formStore = memoryFormStoreFactory.createFromFormKey('copy_locale', undefined, undefined, undefined, {
                locales: locales.filter((locale) => locale !== this.resourceFormStore.locale?.get()),
            });
        }
    }

    getNode() {
        const {
            resourceFormStore: {
                id,
                locale: currentLocale,
            },
            locales,
        } = this;

        if (!id) {
            return null;
        }

        if (!locales || !currentLocale) {
            throw new Error('The CopyLocaleToolbarAction for pages only works with locales!');
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={(this.formStore.data.locales?.length ?? 0) === 0}
                confirmLoading={this.copying}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.copy_locale"
                onCancel={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showCopyLocaleDialog}
                title={translate('sulu_admin.copy_locale')}
            >
                <div className={copyLocaleActionStyles.dialog}>
                    <FormContainer
                        onSubmit={this.handleConfirm}
                        store={this.formStore}
                    />
                </div>
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        const {
            visible_condition: visibleCondition,
        } = this.options;

        const {id} = this.resourceFormStore;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (visibleConditionFulfilled) {
            return {
                disabled: !id,
                icon: 'su-copy',
                label: translate('sulu_admin.copy_locale'),
                onClick: action(() => {
                    this.showCopyLocaleDialog = true;
                }),
                type: 'button',
            };
        }
    }

    @action handleConfirm = () => {
        this.copying = true;
        const {
            resourceFormStore: {
                id,
                locale,
                options: {
                    webspace,
                },
                resourceKey,
            },
        } = this;

        const data = this.formStore.data;
        const options = Object.keys(data).reduce((acc, key) => {
            const value = data[key];
            if (key === 'locales') {
                key = 'dest';
            }
            acc[key] = value;

            return acc;
        }, {});

        ResourceRequester.post(
            resourceKey,
            undefined,
            {
                id,
                locale,
                action: 'copy-locale',
                webspace,
                ...options,
            }
        ).then(action(() => {
            this.copying = false;
            this.showCopyLocaleDialog = false;
            this.form.showSuccessSnackbar();
            this.destroyFormStore();
        }));
    };

    @action handleClose = () => {
        this.showCopyLocaleDialog = false;
        this.destroyFormStore();
    };

    @action handleCheckboxChange = (checked: boolean, value?: string | number) => {
        if (checked && typeof value === 'string' && !this.selectedLocales.includes(value)) {
            this.selectedLocales.push(value);
        } else {
            this.selectedLocales.splice(this.selectedLocales.findIndex((locale) => locale === value), 1);
        }
    };

    @action destroyFormStore = () => {
        this.formStore.destroy();

        this.formStore = memoryFormStoreFactory.createFromFormKey('copy_locale', undefined, undefined, undefined, {
            locales: this.locales?.filter((locale) => locale !== this.resourceFormStore.locale?.get()),
        });
    };
}
