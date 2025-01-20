// @flow
import React, {Component} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import FormOverlay from '../FormOverlay';
import MemoryFormStoreFactory from '../Form/stores/memoryFormStoreFactory';
import {Requester} from '../../services';
import {translate} from '../../utils';
import ActionButton from './ActionButton';
import type {FormStoreInterface} from '../Form';

type Props = {|
    context?: Object,
    formKey: string,
    source: string,
    url: string,
|};

@observer
class ActionOverlay extends Component<Props> {
    @observable open = false;
    @observable formStore: FormStoreInterface;
    @observable saving = false;
    @observable valid = false;

    constructor(props: Props) {
        super(props);

        this.formStore = MemoryFormStoreFactory.createFromFormKey(props.formKey);
    }

    @action handleOpen = () => {
        this.open = true;
    };

    @action handleConfirm = () => {
        this.saving = true;

        Requester.post(
            this.props.url,
            {
                ...(this.formStore?.data || {}),
                source: this.props.source,
                context: this.props.context ?? {},
            }
        ).then(action(() => {
            this.saving = false;
            this.open = false;
            this.handleClose();
        })).catch(action(() => {
            this.saving = false;
            this.open = false;
        }));
    };

    @action handleClose = () => {
        this.formStore = MemoryFormStoreFactory.createFromFormKey(this.props.formKey);
        this.saving = false;
        this.open = false;
    };

    handleFieldFinish = () => {
        this.valid = this.formStore?.dirty && this.formStore?.validate();
    };

    render() {
        return (
            <>
                <ActionButton
                    messages={{
                        title: translate('sulu_admin.ai_action_button'),
                    }}
                    onClick={this.handleOpen}
                />

                <FormOverlay
                    confirmDisabled={!this.valid}
                    confirmLoading={this.saving}
                    confirmText={translate('sulu_admin.ai_action_confirm')}
                    formStore={this.formStore}
                    onClose={this.handleClose}
                    onConfirm={this.handleConfirm}
                    onFieldFinish={this.handleFieldFinish}
                    open={this.open}
                    size="small"
                    title={translate('sulu_admin.ai_action_title')}
                />
            </>
        );
    }
}

export default ActionOverlay;
