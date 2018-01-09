// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {AbstractAdapter, FlatStructureStrategy, InfiniteLoadingStrategy} from 'sulu-admin-bundle/containers';
import MediaCardAdapter from './MediaCardAdapter';

const EDIT_ICON = 'pencil';

@observer
export default class MediaCardOverviewAdapter extends AbstractAdapter {
    static LoadingStrategy = InfiniteLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    render() {
        return (
            <MediaCardAdapter
                {...this.props}
                icon={EDIT_ICON}
            />
        );
    }
}
