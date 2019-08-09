// @flow
import type {Node} from 'react';
import {ResourceFormStore} from '../../../containers/Form';
import type {ToolbarAction, ToolbarItemConfig} from '../../../containers/Toolbar/types';
import Router from '../../../services/Router';
import Form from '../Form';

export default class AbstractFormToolbarAction implements ToolbarAction {
    resourceFormStore: ResourceFormStore;
    form: Form;
    router: Router;
    locales: ?Array<string>;
    options: {[key: string]: mixed};

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed}
    ) {
        this.resourceFormStore = resourceFormStore;
        this.form = form;
        this.router = router;
        this.locales = locales;
        this.options = options;
    }

    setLocales(locales: Array<string>) {
        this.locales = locales;
    }

    getNode(): Node {
        return null;
    }

    getToolbarItemConfig(): ?ToolbarItemConfig {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }
}
