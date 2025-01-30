// @flow
import {action, computed, observable} from 'mobx';
import type {Webspace} from './types';

class WebspaceStore {
    @observable allWebspaces: Array<Webspace>;

    @action setWebspaces(webspaces: Array<Webspace>) {
        this.allWebspaces = webspaces;
    }

    @computed get grantedWebspaces(): Array<Webspace> {
        return this.allWebspaces.filter((webspace) => {
            return webspace._permissions.view === true;
        });
    }

    hasWebspace(webspaceKey: string): boolean {
        return !!this.allWebspaces.find((webspace) => webspace.key === webspaceKey);
    }

    getWebspace(webspaceKey: string): Webspace {
        const webspace = this.allWebspaces.find((webspace) => webspace.key === webspaceKey);

        if (!webspace) {
            throw new Error('Webspace "' + webspaceKey + '" not found');
        }

        return webspace;
    }
}

export default new WebspaceStore();
