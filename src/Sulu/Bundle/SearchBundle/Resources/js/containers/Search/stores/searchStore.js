// @flow
import {action, autorun, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';

class SearchStore {
    @observable query: ?string = undefined;
    @observable indexName: ?string = undefined;
    @observable result: Array<Object> = [];
    @observable page: number = 1;
    @observable limit: number = 10;
    @observable loading: boolean = false;
    pages: ?number = undefined;
    total: ?number = undefined;

    constructor() {
        autorun(() => {
            if (!this.query) {
                this.resetResults();
                return;
            }

            this.setLoading(true);
            ResourceRequester.getList('search',
                {
                    q: this.query,
                    index: this.indexName,
                    page: this.page, limit:
                        this.limit,
                }
            ).then(action((response) => {
                this.setLoading(false);
                this.total = response.total;
                this.page = response.page;
                this.pages = response.pages;
                this.limit = response.limit;
                this.result = response._embedded.result;
            }));
        });
    }

    @action search(query: ?string, index: ?string) {
        this.resetResults();
        this.query = query;
        this.indexName = index;
    }

    @action resetResults() {
        this.result.splice(0, this.result.length);
        this.page = 1;
        this.pages = undefined;
        this.total = undefined;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action setPage(page: number) {
        this.page = page;
    }

    @action setLimit(limit: number) {
        this.page = 1;
        this.limit = limit;
    }
}

export default new SearchStore();
