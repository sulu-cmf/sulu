// @flow
import React from 'react';
import {computed, observable} from 'mobx';
import userStore from 'sulu-admin-bundle/stores/userStore';
import {observer} from 'mobx-react';
import LocationComponent from '../../../containers/Location';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Location as LocationValue} from '../../../types';
import type {IObservableValue} from 'mobx/lib/mobx';

@observer
export default class Location extends React.Component<FieldTypeProps<?LocationValue>> {
    handleChange = (value: ?LocationValue) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {
            disabled,
            value,
        } = this.props;

        return (
            <LocationComponent
                disabled={!!disabled}
                locale={this.locale.get()}
                onChange={this.handleChange}
                value={value}
            />
        );
    }

    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }
}
