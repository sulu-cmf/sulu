// @flow
import React from 'react';
import viewStore from './stores/ViewStore';

export default class ViewRenderer extends React.PureComponent<{name: string, parameters: Object}> {
    render() {
        const view = viewStore.get(this.props.name);
        if (!view) {
            throw new Error('View "' + this.props.name + '" has not been found');
        }

        return React.createElement(view, this.props.parameters);
    }
}
