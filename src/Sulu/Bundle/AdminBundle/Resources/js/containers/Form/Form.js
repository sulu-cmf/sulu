// @flow
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import Loader from '../../components/Loader';
import ResourceStore from '../../stores/ResourceStore';
import type {Schema} from '../../stores/ResourceStore/types';
import Renderer from './Renderer';

type Props = {
    schema: Schema,
    store: ResourceStore,
    onSubmit: () => void,
};

@observer
export default class Form extends React.PureComponent<Props> {
    renderer: ?ElementRef<typeof Renderer>;

    /** @public */
    submit = () => {
        if (!this.renderer) {
            return;
        }

        this.renderer.submit();
    };

    handleSubmit = () => {
        this.props.onSubmit();
    };

    handleChange = (name: string, value: mixed) => {
        this.props.store.set(name, value);
    };

    setRenderer = (renderer: ?ElementRef<typeof Renderer>) => {
        this.renderer = renderer;
    };

    render() {
        const {schema, store} = this.props;
        return store.loading
            ? <Loader />
            : <Renderer
                ref={this.setRenderer}
                onSubmit={this.handleSubmit}
                onChange={this.handleChange}
                schema={schema}
                data={store.data}
            />;
    }
}
