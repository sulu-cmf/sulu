// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Icon, Loader} from 'sulu-admin-bundle/components';
import Pagination from 'sulu-admin-bundle/components/Pagination';
import {Router} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import jsonpointer from 'json-pointer';
import searchStore from './stores/searchStore';
import indexStore from './stores/indexStore';
import SearchField from './SearchField';
import SearchResult from './SearchResult';
import searchStyles from './search.scss';
import searchResultStyles from './searchResult.scss';
import type {Index} from './types';

type Props = {|
    router: Router,
|};

@observer
class Search extends React.Component<Props> {
    @observable query: ?string = undefined;
    @observable indexes: ?{[indexName: string]: Index} = undefined;
    @observable indexName: ?string = undefined;

    @action componentDidMount() {
        this.query = searchStore.query;
        this.indexName = searchStore.indexName;
        indexStore.loadIndexes().then(action((indexes: Array<Index>) => {
            this.indexes = indexes.reduce((indexesObject: Object, index) => {
                indexesObject[index.indexName] = index;
                return indexesObject;
            }, {});
        }));
    }

    @action handleIndexChange = (indexName: ?string) => {
        this.indexName = indexName;
    };

    @action handleQueryChange = (query: ?string) => {
        this.query = query;
    };

    handleLimitChange = (limit: number) => {
        searchStore.setLimit(limit);
    };

    handlePageChange = (page: number) => {
        searchStore.setPage(page);
    };

    handleSearch = () => {
        searchStore.search(this.query, this.indexName);
    };

    handleResultClick = (index: number) => {
        if (!this.indexes) {
            throw new Error(
                'The indexes must be available to route to a search result! This should not happen and is likely a bug.'
            );
        }

        const result = searchStore.result[index];
        const {
            route: {
                name: routeName,
                resultToRoute,
            },
        } = this.indexes[result.document.index];

        const {router} = this.props;
        router.navigate(
            routeName,
            Object.keys(resultToRoute).reduce((parameters, resultPath) => {
                parameters[resultToRoute[resultPath]] = jsonpointer.get(result.document, '/' + resultPath);
                return parameters;
            }, {})
        );
    };

    render() {
        const {indexes} = this;

        if (!indexes) {
            return <Loader />;
        }

        const results = searchStore.result.map((result, index) => (
            <SearchResult
                description={result.document.description}
                icon={indexes[result.document.index].icon}
                image={result.document.imageUrl}
                index={index}
                key={result.document.index + '_' + result.document.id + '_' + result.document.locale}
                locale={result.document.locale}
                onClick={this.handleResultClick}
                resource={
                    indexes[result.document.index]
                        ? indexes[result.document.index].name
                        : ''
                }
                title={result.document.title}
            />
        ));

        return (
            <div className={searchStyles.search}>
                <SearchField
                    indexes={indexes}
                    indexName={this.indexName}
                    onIndexChange={this.handleIndexChange}
                    onQueryChange={this.handleQueryChange}
                    onSearch={this.handleSearch}
                    query={this.query || undefined}
                />
                {searchStore.loading &&
                    <Loader />
                }
                {!searchStore.loading && searchStore.query && searchStore.result.length === 0 &&
                    <div className={searchStyles.nothingHint}>
                        <div className={searchStyles.nothingIcon}>
                            <Icon name="su-battery-low" />
                        </div>
                        {translate('sulu_search.nothing_found')}
                    </div>
                }
                {!searchStore.loading && searchStore.result.length > 0 &&
                    <div className={searchResultStyles.searchResultsOuterContainer}>
                        <Pagination
                            currentLimit={searchStore.limit}
                            currentPage={searchStore.page}
                            loading={searchStore.loading}
                            onLimitChange={this.handleLimitChange}
                            onPageChange={this.handlePageChange}
                            totalPages={searchStore.pages}
                        >
                            {results}
                        </Pagination>
                    </div>
                }
            </div>
        );
    }
}

export default Search;
