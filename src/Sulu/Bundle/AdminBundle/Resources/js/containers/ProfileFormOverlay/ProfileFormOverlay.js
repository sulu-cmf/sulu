// @flow
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Loader from '../../components/Loader/Loader';
import Overlay from '../../components/Overlay';
import Form from '../../containers/Form';
import ResourceRequester from '../../services/ResourceRequester';
import userStore from '../../stores/userStore';
import {translate} from '../../utils/Translator';
import MemoryFormStore from '../Form/stores/MemoryFormStore';
import metadataStore from '../Form/stores/metadataStore';
import type {RawSchema} from '../Form/types';
import profileFormOverlayStyles from './profileFormOverlay.scss';

type Props = {
    onClose: () => void,
    open: boolean,
}

const FORM_KEY = 'profile_details';
const RESOURCE_KEY = 'profile';

@observer
class ProfileFormOverlay extends React.Component<Props> {
    formRef: ?Form;
    title: string;
    @observable formStore: MemoryFormStore;
    saving: boolean = false;

    constructor(props: Props) {
        super(props);

        Promise.all([
            metadataStore.getSchema(FORM_KEY),
            metadataStore.getJsonSchema(FORM_KEY),
            ResourceRequester.get(RESOURCE_KEY),
        ]).then(this.handleResponse);
    }

    @action handleResponse = ([schema, jsonSchema, data]: [RawSchema, Object, Object]) => {
        this.formStore = new MemoryFormStore(data, schema, jsonSchema);
    };

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    handleConfirm = () => {
        if (this.formRef) {
            this.formRef.submit();
        }
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleSubmit = () => {
        this.saving = true;
        ResourceRequester.put(RESOURCE_KEY, this.formStore.data).then(() => {
            userStore.setFullName(this.formStore.data.firstName + ' ' + this.formStore.data.lastName);
            this.props.onClose();
            this.saving = false;
        });
    };

    render() {
        return (
            <Overlay
                confirmLoading={!this.formStore || this.saving}
                confirmText={translate('sulu_admin.save')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.props.open}
                size="large"
                title={translate('sulu_admin.edit_profile')}
            >
                {this.formStore !== undefined
                    ? <div className={profileFormOverlayStyles.overlay}>
                        <Form
                            onSubmit={this.handleSubmit}
                            ref={this.setFormRef}
                            store={this.formStore}
                        />
                    </div>
                    : <Loader />
                }

            </Overlay>
        );
    }
}

export default ProfileFormOverlay;
