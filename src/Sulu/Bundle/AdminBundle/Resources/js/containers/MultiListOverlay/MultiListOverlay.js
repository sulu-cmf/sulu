// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import ListStore from '../../containers/List/stores/ListStore';
import ListOverlay from '../ListOverlay';
import type {OverlayType} from '../ListOverlay';

const USER_SETTINGS_KEY = 'multi_list_overlay';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems?: boolean,
    clearSelectionOnClose: boolean,
    confirmLoading?: boolean,
    listKey: string,
    disabledIds?: Array<string | number>,
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItems: Array<Object>) => void,
    open: boolean,
    options?: Object,
    overlayType: OverlayType,
    resourceKey: string,
    preSelectedItems: Array<Object>,
    reloadOnOpen?: boolean,
    title: string,
|};

export default class MultiListOverlay extends React.Component<Props> {
    listStore: ListStore;
    page: IObservableValue<number> = observable.box(1);

    static defaultProps = {
        clearSelectionOnClose: false,
        overlayType: 'overlay',
        preSelectedItems: [],
    };

    constructor(props: Props) {
        super(props);

        const {listKey, locale, options, preSelectedItems, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        this.listStore = new ListStore(
            resourceKey,
            listKey,
            USER_SETTINGS_KEY,
            observableOptions,
            options,
            preSelectedItems.map((preSelectedItem) => preSelectedItem.id)
        );
    }

    componentWillUnmount() {
        this.listStore.destroy();
    }

    handleConfirm = () => {
        this.props.onConfirm(this.listStore.selections);
    };

    render() {
        const {
            adapter,
            allowActivateForDisabledItems,
            clearSelectionOnClose,
            confirmLoading,
            disabledIds,
            onClose,
            open,
            overlayType,
            preSelectedItems,
            reloadOnOpen,
            title,
        } = this.props;

        return (
            <ListOverlay
                adapter={adapter}
                allowActivateForDisabledItems={allowActivateForDisabledItems}
                clearSelectionOnClose={clearSelectionOnClose}
                confirmLoading={confirmLoading}
                disabledIds={disabledIds}
                listStore={this.listStore}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                overlayType={overlayType}
                preSelectedItems={preSelectedItems}
                reloadOnOpen={reloadOnOpen}
                title={title}
            />
        );
    }
}
