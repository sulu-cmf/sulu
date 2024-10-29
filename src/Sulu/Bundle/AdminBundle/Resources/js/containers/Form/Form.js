// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import log from 'loglevel';
import Loader from '../../components/Loader';
import Hint from '../../components/Hint';
import Router from '../../services/Router';
import {translate} from '../../utils';
import Renderer from './Renderer';
import FormInspector from './FormInspector';
import GhostDialog from './GhostDialog';
import MissingTypeDialog from './MissingTypeDialog';
import type {ChangeContext, FormStoreInterface} from './types';

type Props = {|
    onError?: (errors: Object) => void,
    onMissingTypeCancel?: () => void,
    onSubmit: (action: ?string | {[string]: any}) => ?Promise<Object>,
    onSuccess?: () => void,
    router?: Router,
    store: FormStoreInterface,
|};

@observer
class Form extends React.Component<Props> {
    @observable showAllErrors = false;
    @observable displayGhostDialog = false;
    displayGhostDialogDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.displayGhostDialogDisposer = autorun(() => {
            const {store} = this.props;
            const {
                data: {
                    availableLocales,
                },
                loading,
                locale,
            } = store;

            if (loading) {
                this.hideGhostDialog();
                return;
            }

            if (availableLocales && locale && !availableLocales.includes(locale.get())) {
                this.showGhostDialog();
            }
        });
    }

    componentWillUnmount() {
        this.displayGhostDialogDisposer();
    }

    @computed get formInspector(): FormInspector {
        return new FormInspector(this.props.store);
    }

    /** @public */
    @action submit = (options: ?string | {[string]: any}) => {
        if (typeof options === 'string') {
            log.warn(
                'Passing a string to the "submit" method is deprecated since 2.2 and will be removed. ' +
                'Pass an object with an "action" property instead.'
            );
        }

        const {onError, onSubmit, store} = this.props;

        this.showAllErrors = true;

        if (store.validate()) {
            const submitPromise = onSubmit(options);
            if (submitPromise) {
                return submitPromise.then((response) => {
                    this.formInspector.triggerSaveHandler(options);
                    return response;
                });
            }

            return submitPromise;
        }

        if (onError) {
            return onError(store.errors);
        }
    };

    handleChange = (name: string, value: mixed, context?: ChangeContext) => {
        this.props.store.change(name, value, context);
    };

    @action showGhostDialog() {
        this.displayGhostDialog = true;
    }

    @action hideGhostDialog() {
        this.displayGhostDialog = false;
    }

    @action handleGhostDialogCancel = () => {
        this.hideGhostDialog();
    };

    @action handleGhostDialogConfirm = (locale: string, options: Object) => {
        const {store} = this.props;

        if (!store.copyFromLocale) {
            return;
        }

        store.copyFromLocale(locale, options);
        this.hideGhostDialog();
    };

    @action handleMissingTypeDialogConfirm = (type: string) => {
        const {store} = this.props;

        store.changeType(type);
    };

    @action handleMissingTypeDialogCancel = () => {
        const {onMissingTypeCancel} = this.props;

        if (onMissingTypeCancel) {
            onMissingTypeCancel();
        }
    };

    handleFieldFinish = (dataPath: string, schemaPath: string) => {
        log.debug(
            'Finished editing field with dataPath "' + dataPath + '" and schemaPath "' + schemaPath + '"',
            toJS(this.formInspector.getValueByPath(dataPath))
        );
        const {store} = this.props;

        store.validate();
        this.formInspector.finishField(dataPath, schemaPath);
    };

    render() {
        const {onSuccess, router, store} = this.props;
        const {
            data: {
                availableLocales,
            },
        } = store;

        if (store.forbidden) {
            return <Hint icon="su-lock" title={translate('sulu_admin.no_permissions')} />;
        }

        if (store.notFound) {
            return <Hint icon="su-battery-low" title={translate('sulu_admin.not_found')} />;
        }

        if (store.unexpectedError) {
            return <Hint icon="su-exclamation-triangle" title={translate('sulu_admin.unexpected_error')} />;
        }

        if (store.loading) {
            return <Loader />;
        }

        return (
            <Fragment>
                {store.id && availableLocales &&
                    <GhostDialog
                        locales={availableLocales}
                        onCancel={this.handleGhostDialogCancel}
                        onConfirm={this.handleGhostDialogConfirm}
                        open={this.displayGhostDialog}
                    />
                }
                <MissingTypeDialog
                    onCancel={this.handleMissingTypeDialogCancel}
                    onConfirm={this.handleMissingTypeDialogConfirm}
                    open={store.hasInvalidType}
                    types={store.types}
                />
                {!store.hasInvalidType &&
                    <Renderer
                        data={store.data}
                        dataPath=""
                        errors={store.errors}
                        formInspector={this.formInspector}
                        onChange={this.handleChange}
                        onFieldFinish={this.handleFieldFinish}
                        onSuccess={onSuccess}
                        router={router}
                        schema={store.schema}
                        schemaPath=""
                        showAllErrors={this.showAllErrors}
                        value={store.data}
                    />
                }
            </Fragment>
        );
    }
}

export default Form;
