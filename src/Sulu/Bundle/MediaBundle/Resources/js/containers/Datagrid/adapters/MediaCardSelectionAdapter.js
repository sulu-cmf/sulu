// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const SELECT_ICON = 'check';

@observer
export default class MediaCardSelectionAdapter extends AbstractAdapter {
    static getLoadingStrategy: () => string = () => { return 'infiniteScroll'; };
    static getStorageStrategy: () => string = () => { return 'flat'; };

    handleItemClick = (itemId: string | number, selected: boolean) => {
        const {onItemSelectionChange} = this.props;

        if (onItemSelectionChange) {
            onItemSelectionChange(itemId, selected);
        }
    };

    render() {
        const {onItemSelectionChange} = this.props;

        return (
            <MediaCardAdapter
                {...this.props}
                onItemClick={onItemSelectionChange}
                icon={SELECT_ICON}
                showCoverWhenSelected={true}
            />
        );
    }
}
