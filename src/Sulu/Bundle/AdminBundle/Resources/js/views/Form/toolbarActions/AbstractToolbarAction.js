// @flow
import type {Node} from 'react';
import {FormStore} from '../../../containers/Form';
import type {ToolbarAction, ToolbarItemConfig} from '../../../containers/Toolbar/types';
import Router from '../../../services/Router';
import Form from '../../../views/Form';

export default class AbstractFormToolbarAction implements ToolbarAction {
    formStore: FormStore;
    form: Form;
    router: Router;
    locales: ?Array<string>;

    constructor(formStore: FormStore, form: Form, router: Router, locales?: Array<string>) {
        this.formStore = formStore;
        this.form = form;
        this.router = router;
        this.locales = locales;
    }

    setLocales(locales: Array<string>) {
        this.locales = locales;
    }

    getNode(): Node {
        return null;
    }

    getToolbarItemConfig(): ToolbarItemConfig {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }
}
