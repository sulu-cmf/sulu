// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {MultiItemSelection} from '../../components';
import DatagridOverlay from './DatagridOverlay';

type Props = {
    onChange: (selectedIds: Array<string | number>) => void,
    icon: string,
    resourceKey: string,
    preSelectedIds?: Array<string | number>,
    title: string,
};

@observer
export default class Assignment extends React.Component<Props> {
    static defaultProps = {
        icon: 'su-plus',
        preSelectedIds: [],
        resourceKey: 'snippets', // TODO remove, only here for testing purposes
        title: 'Assignment', // TODO remove, only here for testing purposes
    };

    @observable overlayOpen: boolean = false;

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action handleOverlayOpen = () => {
        this.openOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedIds: Array<string | number>) => {
        this.props.onChange(selectedIds);
        this.closeOverlay();
    };

    render() {
        const {icon, resourceKey, title, preSelectedIds} = this.props;

        return (
            <Fragment>
                <MultiItemSelection
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                />
                <DatagridOverlay
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    resourceKey={resourceKey}
                    preSelectedIds={preSelectedIds}
                    title={title}
                />
            </Fragment>
        );
    }
}
