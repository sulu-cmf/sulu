// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Dialog from '../../components/Dialog';
import FormContainer from './Form';
import memoryFormStoreFactory from './stores/memoryFormStoreFactory';
import type {FormStoreInterface} from '../../../containers/Form';
import {translate} from '../../utils';

type Props = {
    locales: Array<string>,
    onCancel: () => void,
    onConfirm: (locale: string) => void,
    open: boolean,
};

@observer
class GhostDialog extends React.Component<Props> {
    @observable selectedLocale: string;
    formStore: FormStoreInterface;

    constructor(props: Props) {
        super(props);

        this.selectedLocale = this.props.locales[0];
        this.formStore = memoryFormStoreFactory.createFromFormKey('ghost_copy_locale', undefined, undefined, undefined, {
            locales: this.props.locales,
        });
    }

    handleCancel = () => {
        this.props.onCancel();
    };

    handleConfirm = () => {
        const data = this.formStore.data;
        const options = Object.keys(data).reduce((acc, key) => {
            if (key !== 'locale') {
                acc[key] = data[key];
            }

            return acc;
        }, {});

        this.props.onConfirm(this.formStore.data.locale, options);
    };

    render() {
        const {
            open,
        } = this.props;

        return (
            <Dialog
                align="left"
                cancelText={translate('sulu_admin.no')}
                confirmText={translate('sulu_admin.yes')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_admin.ghost_dialog_title')}
            >
                <p>{translate('sulu_admin.ghost_dialog_description')}</p>
                <FormContainer
                    store={this.formStore}
                    onSubmit={this.handleConfirm}
                />
            </Dialog>
        );
    }
}

export default GhostDialog;
