// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {translate} from 'sulu-admin-bundle/utils';
import webspaceStore from '../../../stores/WebspaceStore';
import type {Webspace} from '../../../stores/WebspaceStore/types';

@observer
export default class PageSettingsNavigationSelect extends React.Component<FieldTypeProps<Array<string | number>>> {
    @observable webspace: Webspace;

    componentDidMount() {
        const {formInspector} = this.props;
        webspaceStore.loadWebspace(formInspector.options.webspace).then(action((webspace) => {
            this.webspace = webspace;
        }));
    }

    handleChange = (value: Array<string | number>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {
            value,
        } = this.props;

        if (!this.webspace) {
            return null;
        }

        return (
            <MultiSelect
                allSelectedText={translate('sulu_content.all_navigations')}
                noneSelectedText={translate('sulu_content.no_navigation')}
                onChange={this.handleChange}
                values={value || []}
            >
                {this.webspace.navigations.map(({key, title}) => (
                    <MultiSelect.Option key={key} value={key}>
                        {title}
                    </MultiSelect.Option>
                ))}
            </MultiSelect>
        );
    }
}
