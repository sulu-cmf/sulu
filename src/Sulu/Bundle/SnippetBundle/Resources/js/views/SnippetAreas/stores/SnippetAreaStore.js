// @flow
import {action, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {SnippetArea} from '../types';

export default class SnippetAreaStore {
    @observable snippetAreas: {[key: string]: SnippetArea} = {};
    @observable loading: boolean = true;
    @observable saving: boolean = false;
    @observable deleting: boolean = false;
    webspaceKey: string;

    constructor(webspaceKey: string) {
        this.webspaceKey = webspaceKey;

        ResourceRequester.getList('snippet_areas', {webspace: webspaceKey}).then(action((response) => {
            this.snippetAreas = response._embedded.areas.reduce((snippetAreas, snippetArea) => {
                snippetAreas[snippetArea.key] = snippetArea;

                return snippetAreas;
            }, {});
            this.loading = false;
        }));
    }

    @action save(areaKey: string, defaultUuid: string) {
        this.saving = true;

        return ResourceRequester.put('snippet_areas', {defaultUuid}, {key: areaKey, webspace: this.webspaceKey})
            .then(action((response) => {
                this.snippetAreas[areaKey] = response;
                this.saving = false;
            }));
    }

    @action delete(areaKey: string) {
        this.deleting = true;

        return ResourceRequester.delete('snippet_areas', {key: areaKey, webspace: this.webspaceKey})
            .then(action((response) => {
                this.snippetAreas[areaKey] = response;
                this.deleting = false;
            }));
    }
}
