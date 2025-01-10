// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed} from 'mobx';
import SingleItemSelection from '../../../components/SingleItemSelection';
import {translate} from '../../../utils/Translator';
import SingleListOverlay from '../../SingleListOverlay';
import userStore from '../../../stores/userStore';
import type {FieldTypeProps} from '../../../types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = FieldTypeProps<?string>

@observer
export default class SingleIconSelect extends React.Component<Props> {
    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    handleRemove = () => {
        const {
            onChange,
        } = this.props;

        if (onChange) {
            onChange(undefined);
        }
    };

    @action handleOverlayConfirm = (value: { id: string }) => {
        const {
            onChange,
        } = this.props;

        if (onChange) {
            onChange(value.id);
        }

        this.closeOverlay();
    };

    handleOverlayOpen = () => {
        this.openOverlay();
    };

    handleOverlayClose = () => {
        this.closeOverlay();
    };

    render() {
        const {
            schemaOptions: {
                icon_set: {
                    value: iconSet,
                } = {},
            } = {},
            value,
        } = this.props;

        if (iconSet === typeof 'undefined') {
            throw new Error('The "icon_set" schema option must be defined!');
        }

        return (
            <Fragment>
                <SingleItemSelection
                    emptyText={translate('sulu_admin.single_icon_select.select')}
                    id={value}
                    leftButton={{
                        icon: 'su-magic',
                        onClick: this.handleOverlayOpen,
                    }}
                    onItemClick={this.handleOverlayOpen}
                    onRemove={value ? this.handleRemove : undefined}
                    value={value}
                >
                    {value &&
                        <div>{value}</div>
                    }
                </SingleItemSelection>

                <SingleListOverlay
                    adapter="icon"
                    listKey="icons"
                    locale={this.locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    options={
                        {
                            'icon_set': iconSet,
                        }
                    }
                    preSelectedItem={{'id': value}}
                    resourceKey="icons"
                    title={translate('sulu_admin.single_icon_select.select')}
                />
            </Fragment>
        );
    }
}
