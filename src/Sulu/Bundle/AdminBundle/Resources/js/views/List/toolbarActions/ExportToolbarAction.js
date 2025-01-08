// @flow
import {action, observable} from 'mobx';
import React from 'react';
import {translate} from '../../../utils/Translator';
import Overlay from '../../../components/Overlay';
import Form from '../../../components/Form';
import SingleSelect from '../../../components/SingleSelect';
import resourceRouteRegistry from '../../../services/ResourceRequester/registries/resourceRouteRegistry';
import exportToolbarActionStyles from './exportToolbarAction.scss';
import AbstractListToolbarAction from './AbstractListToolbarAction';

export default class ExportToolbarAction extends AbstractListToolbarAction {
    @observable showOverlay = false;
    @observable delimiter: string = ';';
    @observable enclosure: string = '"';
    @observable escape: string = '\\';
    @observable newLine: string = '\\n';

    getNode() {
        return (
            <Overlay
                confirmDisabled={false}
                confirmLoading={false}
                confirmText={translate('sulu_admin.export')}
                key="sulu_admin.export"
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
                size="small"
                title={translate('sulu_admin.export_overlay_title')}
            >
                <div className={exportToolbarActionStyles.overlay}>
                    <Form>
                        <Form.Section colSpan={6}>
                            <Form.Field
                                description={translate('sulu_admin.delimiter_description')}
                                label={translate('sulu_admin.delimiter')}
                            >
                                <SingleSelect onChange={this.handleDelimiterChanged} value={this.delimiter}>
                                    <SingleSelect.Option value=";">;</SingleSelect.Option>
                                    <SingleSelect.Option value=",">,</SingleSelect.Option>
                                    <SingleSelect.Option value="\t">
                                        {translate('sulu_admin.delimiter_tab')}
                                    </SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                            <Form.Field
                                description={translate('sulu_admin.enclosure_description')}
                                label={translate('sulu_admin.enclosure')}
                            >
                                <SingleSelect onChange={this.handleEnclosureChanged} value={this.enclosure}>
                                    <SingleSelect.Option value='"'>&quot;</SingleSelect.Option>
                                    <SingleSelect.Option value="">
                                        {translate('sulu_admin.enclosure_nothing')}
                                    </SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                        </Form.Section>
                        <Form.Section colSpan={6}>
                            <Form.Field
                                description={translate('sulu_admin.escape_description')}
                                label={translate('sulu_admin.escape')}
                            >
                                <SingleSelect onChange={this.handleEscapeChanged} value={this.escape}>
                                    <SingleSelect.Option value={'\\'}>\</SingleSelect.Option>
                                    <SingleSelect.Option value='"'>&quot;</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                            <Form.Field
                                description={translate('sulu_admin.new_line_description')}
                                label={translate('sulu_admin.new_line')}
                            >
                                <SingleSelect onChange={this.handleNewLineChanged} value={this.newLine}>
                                    <SingleSelect.Option value={'\\n'}>\n</SingleSelect.Option>
                                    <SingleSelect.Option value={'\\r\\n'}>\r\n</SingleSelect.Option>
                                    <SingleSelect.Option value={'\\r'}>\r</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                        </Form.Section>
                    </Form>
                </div>
            </Overlay>
        );
    }

    getToolbarItemConfig() {
        return {
            disabled: this.listStore.data.length === 0,
            icon: 'su-download',
            label: translate('sulu_admin.export'),
            onClick: action(() => {
                this.showOverlay = true;
            }),
            type: 'button',
        };
    }

    @action handleClose = () => {
        this.showOverlay = false;
    };

    @action handleDelimiterChanged = (value: string) => {
        this.delimiter = value;
    };

    @action handleEnclosureChanged = (value: string) => {
        this.enclosure = value;
    };

    @action handleEscapeChanged = (value: string) => {
        this.escape = value;
    };

    @action handleNewLineChanged = (value: string) => {
        this.newLine = value;
    };

    @action handleConfirm = () => {
        const {filterQueryOption} = this.listStore;
        const filter = Object.keys(filterQueryOption).length > 0 ? filterQueryOption : undefined;

        const search = this.listStore.searchTerm.get();

        window.location.assign(resourceRouteRegistry.getUrl('list', this.listStore.resourceKey, {
            _format: 'csv',
            locale: this.list.locale.get(),
            flat: true,
            delimiter: this.delimiter,
            escape: this.escape,
            enclosure: this.enclosure,
            newLine: this.newLine,
            ...this.listStore.options,
            filter,
            search,
        }));
        this.showOverlay = false;
    };
}
