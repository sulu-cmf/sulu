// @flow
import {action, autorun, computed, intercept, observable, untracked} from 'mobx';
import equals from 'fast-deep-equal';
import log from 'loglevel';
import ResourceRequester, {RequestPromise} from '../../../services/ResourceRequester';
import userStore from '../../../stores/userStore';
import metadataStore from './metadataStore';
import type {
    LoadingStrategyInterface,
    ObservableOptions,
    Schema,
    SortOrder,
    StructureStrategyInterface,
} from '../types';
import type {IObservableValue, IValueWillChange} from 'mobx/lib/mobx';

const USER_SETTING_PREFIX = 'sulu_admin.list_store';

const USER_SETTING_ACTIVE = 'active';
const USER_SETTING_SORT_COLUMN = 'sort_column';
const USER_SETTING_SORT_ORDER = 'sort_order';
const USER_SETTING_FILTER = 'filter';
const USER_SETTING_LIMIT = 'limit';
const USER_SETTING_SCHEMA = 'schema';

export default class ListStore {
    @observable pageCount: ?number = 0;
    @observable selections: Array<Object> = [];
    @observable dataLoading: boolean = true;
    @observable deleting: boolean = false;
    @observable deletingSelection: boolean = false;
    @observable moving: boolean = false;
    @observable movingSelection: boolean = false;
    @observable copying: boolean = false;
    @observable ordering: boolean = false;
    @observable schemaLoading: boolean = true;
    @observable shouldReload: boolean = false;
    @observable loadingStrategy: LoadingStrategyInterface;
    @observable structureStrategy: StructureStrategyInterface;
    @observable options: Object;
    @observable schema: Schema;
    @observable forbidden: boolean;
    active: IObservableValue<?string | number> = observable.box();
    filterOptions: IObservableValue<{[string]: mixed}> = observable.box({});
    sortColumn: IObservableValue<string> = observable.box();
    sortOrder: IObservableValue<SortOrder> = observable.box();
    searchTerm: IObservableValue<?string> = observable.box();
    limit: IObservableValue<number> = observable.box(10);
    resourceKey: string;
    listKey: string;
    userSettingsKey: string;
    observableOptions: ObservableOptions;
    localeDisposer: ?() => void;
    searchDisposer: () => void;
    filterDisposer: () => void;
    sortColumnDisposer: () => void;
    sortOrderDisposer: () => void;
    limitDisposer: () => void;
    activeSettingDisposer: () => void;
    sendRequestDisposer: () => void;
    initialSelectionIds: ?Array<string | number>;
    pendingRequest: ?RequestPromise<*>;
    metadataOptions: ?Object;

    static getActiveSetting(listKey: string, userSettingsKey: string): string {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_ACTIVE].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setActiveSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_ACTIVE].join('.');

        userStore.setPersistentSetting(key, value);
    }

    static getFilterSetting(listKey: string, userSettingsKey: string): string {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_FILTER].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setFilterSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_FILTER].join('.');

        userStore.setPersistentSetting(key, value);
    }

    static getSortColumnSetting(listKey: string, userSettingsKey: string): string {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_SORT_COLUMN].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setSortColumnSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_SORT_COLUMN].join('.');

        userStore.setPersistentSetting(key, value);
    }

    static getSortOrderSetting(listKey: string, userSettingsKey: string): string {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_SORT_ORDER].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setSortOrderSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_SORT_ORDER].join('.');

        userStore.setPersistentSetting(key, value);
    }

    static getLimitSetting(listKey: string, userSettingsKey: string): string {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_LIMIT].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setLimitSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_LIMIT].join('.');

        userStore.setPersistentSetting(key, value);
    }

    static getSchemaSetting(listKey: string, userSettingsKey: string): Array<Object> {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_SCHEMA].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setSchemaSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_SCHEMA].join('.');
        userStore.setPersistentSetting(key, value);
    }

    constructor(
        resourceKey: string,
        listKey: string,
        userSettingsKey: string,
        observableOptions: ObservableOptions,
        options: Object = {},
        metadataOptions: ?Object,
        selectionIds: ?Array<string | number>
    ) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.initialSelectionIds = selectionIds;

        this.sendRequestDisposer = autorun(() => {
            if (this.shouldReload) {
                // changing the value of the reload flag will retrigger this autorun and send the request
                this.setShouldReload(false);
            } else {
                this.sendRequest();
            }
        });

        const callResetForChangedObservable = (change: IValueWillChange<*>) => {
            if (this.initialized && change.object.get() !== change.newValue) {
                this.reset();
            }
        };

        const {locale} = this.observableOptions;
        if (locale) {
            this.localeDisposer = intercept(locale, '', (change: IValueWillChange<*>) => {
                callResetForChangedObservable(change);
                return change;
            });
        }

        this.searchDisposer = intercept(this.searchTerm, '', (change: IValueWillChange<*>) => {
            callResetForChangedObservable(change);
            return change;
        });

        this.filterDisposer = intercept(this.filterOptions, '', (change: IValueWillChange<*>) => {
            const oldValue = change.object.get();
            const oldFilteredValue = oldValue ?
                Object.keys(oldValue).reduce((oldFilteredValue, currentKey) => {
                    if (oldValue[currentKey] !== undefined) {
                        oldFilteredValue[currentKey] = oldValue[currentKey];
                    }

                    return oldFilteredValue;
                }, {})
                : {};

            const newValue = change.newValue;
            const newFilteredValue = newValue ?
                Object.keys(newValue).reduce((newFilteredValue, currentKey) => {
                    if (newValue[currentKey] !== undefined) {
                        newFilteredValue[currentKey] = newValue[currentKey];
                    }

                    return newFilteredValue;
                }, {})
                : {};

            if (!equals(oldFilteredValue, newFilteredValue)) {
                callResetForChangedObservable(change);
            }

            if (!equals(oldValue, newValue)) {
                ListStore.setFilterSetting(this.listKey, this.userSettingsKey, change.newValue);
            }

            return change;
        });

        this.sortColumnDisposer = intercept(this.sortColumn, '', (change: IValueWillChange<*>) => {
            ListStore.setSortColumnSetting(this.listKey, this.userSettingsKey, change.newValue);
            callResetForChangedObservable(change);
            return change;
        });

        this.sortOrderDisposer = intercept(this.sortOrder, '', (change: IValueWillChange<*>) => {
            ListStore.setSortOrderSetting(this.listKey, this.userSettingsKey, change.newValue);
            callResetForChangedObservable(change);
            return change;
        });

        this.limitDisposer = intercept(this.limit, '', (change: IValueWillChange<*>) => {
            ListStore.setLimitSetting(this.listKey, this.userSettingsKey, change.newValue);
            callResetForChangedObservable(change);
            return change;
        });

        this.activeSettingDisposer = intercept(this.active, '', (change: IValueWillChange<*>) => {
            ListStore.setActiveSetting(this.listKey, this.userSettingsKey, change.newValue);
            return change;
        });

        metadataStore.getSchema(this.listKey, this.metadataOptions)
            .then(action((schema) => {
                this.schema = schema;
                this.schemaLoading = false;
            }));
    }

    @computed get initialized(): boolean {
        return !!this.loadingStrategy && !!this.structureStrategy && !!this.schema;
    }

    @computed get loading(): boolean {
        return this.dataLoading || this.schemaLoading;
    }

    @computed get data(): Array<*> {
        return this.structureStrategy.data;
    }

    @computed get visibleItems(): Array<*> {
        return this.structureStrategy.visibleItems;
    }

    @computed get activeItems(): ?Array<*> {
        return this.structureStrategy.activeItems;
    }

    @computed get queryOptions(): Object {
        const queryOptions = {...this.options};

        const {locale} = this.observableOptions;
        if (locale) {
            queryOptions.locale = locale.get();
        }

        return queryOptions;
    }

    @computed.struct get filterQueryOption() {
        const filterOptions = this.filterOptions.get();

        return Object.keys(filterOptions).reduce((filterQueryOption, column) => {
            if (filterOptions[column] !== undefined) {
                filterQueryOption[column] = filterOptions[column];
            }

            return filterQueryOption;
        }, {});
    }

    @computed get userSchema(): Schema {
        if (!this.initialized) {
            return {};
        }

        const schemaSettings = ListStore.getSchemaSetting(this.listKey, this.userSettingsKey) || [];
        const userSchema = {};

        for (const schemaSettingsEntry of schemaSettings) {
            if (!this.schema.hasOwnProperty(schemaSettingsEntry.schemaKey)) {
                continue;
            }

            userSchema[schemaSettingsEntry.schemaKey] = {
                ...this.schema[schemaSettingsEntry.schemaKey],
                visibility: schemaSettingsEntry.visibility,
            };
        }

        for (const schemaKey of Object.keys(this.schema)) {
            if (!userSchema.hasOwnProperty(schemaKey)) {
                userSchema[schemaKey] = this.schema[schemaKey];
            }
        }

        return userSchema;
    }

    changeUserSchema = (schema: Schema) => {
        const schemaSettings = [];
        Object.keys(schema).map((schemaKey) => {
            const schemaEntry = schema[schemaKey];
            schemaSettings.push(
                {
                    schemaKey,
                    visibility: schemaEntry.visibility,
                }
            );
        });
        ListStore.setSchemaSetting(this.listKey, this.userSettingsKey, schemaSettings);
    };

    @computed get filterableFields(): ?Schema {
        if (!this.schema) {
            return undefined;
        }

        return Object.keys(this.schema).reduce(
            (filterableFields, schemaKey) => {
                if (this.schema[schemaKey].filterType){
                    filterableFields[schemaKey] = this.schema[schemaKey];
                }

                return filterableFields;
            },
            {}
        );
    }

    @computed get fields(): Array<string> {
        const fields = [];
        Object.keys(this.userSchema).forEach((schemaKey) => {
            const schemaEntry = this.userSchema[schemaKey];
            if (schemaEntry.visibility === 'yes' || schemaEntry.visibility === 'always') {
                fields.push(schemaKey);
            }
        });

        // TODO do not hardcode id but use metdata instead
        if (!fields.includes('id')) {
            fields.push('id');
        }

        return fields;
    }

    @action updateLoadingStrategy = (loadingStrategy: LoadingStrategyInterface) => {
        if (this.loadingStrategy && this.loadingStrategy === loadingStrategy) {
            return;
        }

        if (this.loadingStrategy) {
            this.reset();
        }

        if (this.structureStrategy) {
            loadingStrategy.setStructureStrategy(this.structureStrategy);
            this.structureStrategy.clear();
        }

        this.loadingStrategy = loadingStrategy;
    };

    @action updateStructureStrategy = (structureStrategy: StructureStrategyInterface) => {
        if (this.structureStrategy === structureStrategy) {
            return;
        }

        if (this.loadingStrategy) {
            this.loadingStrategy.setStructureStrategy(structureStrategy);
        }

        const hadStructureStrategy = !!this.structureStrategy;
        this.structureStrategy = structureStrategy;

        if (hadStructureStrategy) {
            // force a reload to match new structure
            this.reload();
        }
    };

    @action clear = () => {
        if (this.structureStrategy) {
            this.structureStrategy.clear();
        }
    };

    @action reset() {
        const page = this.getPage();

        this.clear();

        this.pageCount = 0;

        if (page && page > 1) {
            this.setPage(1);
        }
    }

    @action reload() {
        this.setShouldReload(true);
    }

    findById(id: string | number): ?Object {
        return this.structureStrategy.findById(id);
    }

    delete = (id: string | number, options: Object): Promise<Object> => {
        this.deleting = true;

        return ResourceRequester.delete(this.resourceKey, {...this.queryOptions, ...options, id})
            .then(action(() => {
                this.deleting = false;
                this.deselectById(id);
                this.remove(id);
            }))
            .catch(action((error) => {
                this.deleting = false;
                throw error;
            }));
    };

    requestMove(id: string | number, parentId: string | number) {
        const queryOptions = {
            ...this.options,
            action: 'move',
            destination: parentId,
        };

        const {locale} = this.observableOptions;
        if (locale) {
            queryOptions.locale = locale.get();
        }

        return ResourceRequester.post(this.resourceKey, undefined, {...queryOptions, id});
    }

    move = (id: string | number, parentId: string | number) => {
        this.moving = true;

        return this.requestMove(id, parentId)
            .then(action(() => {
                this.moving = false;
                this.activate(id);
                this.clear();
            }));
    };

    @action moveSelection = (parentId: string | number) => {
        const {selectionIds} = this;
        this.movingSelection = true;

        return Promise.all(selectionIds.map((selectionId: string | number) => this.requestMove(selectionId, parentId)))
            .then(action(() => {
                this.movingSelection = false;
                this.clear();
                this.activate(parentId);
            }));
    };

    copy = (id: string | number, parentId: string | number, callback: ?(response: Object) => void) => {
        const queryOptions = {
            ...this.options,
            action: 'copy',
            destination: parentId,
        };

        const {locale} = this.observableOptions;
        if (locale) {
            queryOptions.locale = locale.get();
        }

        this.copying = true;

        return ResourceRequester.post(this.resourceKey, undefined, {...queryOptions, id})
            .then(action((response) => {
                this.copying = false;
                callback?.(response);
                // TODO do not hardcode "id", but use some metadata instead
                this.activate(response.id);
                this.clear();
            }));
    };

    @action deleteSelection = () => {
        const deletePromises = [];
        this.deletingSelection = true;
        this.selectionIds.forEach((id) => {
            deletePromises.push(
                ResourceRequester.delete(this.resourceKey, {...this.queryOptions, id})
                    .catch((error) => {
                        if (error.status !== 404) {
                            return Promise.reject(error);
                        }
                    })
            );
        });

        return Promise.all(deletePromises)
            .then(action(() => {
                this.selectionIds.forEach(this.remove);
                this.clearSelection();
                this.reload();
                this.deletingSelection = false;
            }))
            .catch(action((error) => {
                this.deletingSelection = false;

                return Promise.reject(error);
            }));
    };

    remove = (identifier: string | number): void => {
        this.structureStrategy.remove(identifier);
    };

    sendRequest = () => {
        if (!this.initialized) {
            return;
        }

        const observableOptions = {};

        for (const key in this.observableOptions) {
            observableOptions[key] = this.observableOptions[key].get();
        }

        this.setDataLoading(true);
        this.setForbidden(false);

        const active = this.active.get();
        const options = {...observableOptions, ...this.options};

        if (this.initialSelectionIds) {
            options.selectedIds = this.initialSelectionIds.join(',');
        }

        if (!options.selectedIds) {
            if (active && untracked(() => !this.structureStrategy.findById(active))) {
                this.structureStrategy.clear();
                options.expandedIds = active;
            }

            if (!options.expandedIds && active) {
                options.parentId = active;
            }
        }

        options.sortBy = this.sortColumn.get();
        options.sortOrder = this.sortOrder.get();
        options.limit = this.limit.get();
        options.fields = this.fields;
        if (Object.keys(this.filterQueryOption).length > 0) {
            options.filter = this.filterQueryOption;
        }

        if (this.searchTerm.get()) {
            options.search = this.searchTerm.get();
        }

        log.info('List loads "' + this.resourceKey + '" data with the following options:', options);

        if (this.pendingRequest) {
            this.pendingRequest.abort();
        }

        this.pendingRequest = this.loadingStrategy.load(
            this.resourceKey,
            options,
            (options.selectedIds || options.expandedIds) ? undefined : active
        ).then(action((response) => {
            this.pendingRequest = undefined;
            this.pageCount = response.pages;
            this.setDataLoading(false);

            if (this.initialSelectionIds) {
                this.initialSelectionIds
                    .map((selectionId) => this.findById(selectionId))
                    .forEach((selectionRow) => {
                        if (!selectionRow) {
                            return;
                        }

                        this.select(selectionRow);
                    });
                this.initialSelectionIds = undefined;
            }
        })).catch((response) => {
            if (response.name === 'AbortError') {
                return;
            }

            this.pendingRequest = undefined;
            if (this.active.get() && response.status === 404) {
                // need to set the user setting to null manually, because the autorun runs too late
                ListStore.setActiveSetting(this.listKey, this.userSettingsKey, undefined);
                this.setActive(undefined);
                return;
            }

            if (response.status === 403) {
                this.setForbidden(true);
            }

            this.setDataLoading(false);
        });
    };

    updateSelectionIds(newSelectionIds: Array<string | number>) {
        const oldSelectionIds = this.selectionIds;

        if (oldSelectionIds === newSelectionIds) {
            return;
        }

        const removedValues = oldSelectionIds.filter((x) => !newSelectionIds.includes(x));
        const addedValues = newSelectionIds.filter((x) => !oldSelectionIds.includes(x));
        const notLoadedIds = [];

        removedValues.forEach((oldId: string | number) => {
            this.deselectById(oldId);
        });

        addedValues.forEach((newId: string | number) => {
            const row = this.findById(newId);

            if (!row) {
                notLoadedIds.push(newId);

                return;
            }

            this.select(row);
        });

        if (!notLoadedIds.length) {
            return;
        }

        const observableOptions = {};

        for (const key in this.observableOptions) {
            observableOptions[key] = this.observableOptions[key].get();
        }

        this.setDataLoading(true);
        const options = {...observableOptions, ...this.options};

        options.selectedIds = newSelectionIds.join(',');

        this.loadingStrategy.load(
            this.resourceKey,
            options
        ).then(action(() => {
            this.setDataLoading(false);

            newSelectionIds
                .map((selectionId) => this.findById(selectionId))
                .forEach((selectionRow) => {
                    if (!selectionRow) {
                        return;
                    }

                    this.select(selectionRow);
                });
        }));
    }

    @action setDataLoading(dataLoading: boolean) {
        this.dataLoading = dataLoading;
    }

    @action setForbidden(forbidden: boolean) {
        this.forbidden = forbidden;
    }

    @action setShouldReload(shouldReload: boolean) {
        this.shouldReload = shouldReload;
    }

    getPage() {
        return this.observableOptions.page.get();
    }

    @action setPage(page: number) {
        this.observableOptions.page.set(page);
    }

    @action setLimit(limit: number) {
        this.limit.set(limit);
    }

    @action setActive(active: ?string | number) {
        this.active.set(active);
    }

    @action activate(id: ?string | number) {
        // force reload by changing the active item to undefined before actually setting it
        this.setActive(undefined);
        this.setActive(id);

        if (this.structureStrategy.activate) {
            this.structureStrategy.activate(id);
        }
    }

    @action deactivate(id: ?string | number) {
        if (this.structureStrategy.deactivate) {
            this.structureStrategy.deactivate(id);
        }
    }

    @action sort(column: string, order: SortOrder) {
        this.sortColumn.set(column);
        this.sortOrder.set(order);
    }

    @action order(id: string | number, order: number) {
        this.ordering = true;

        return ResourceRequester.post(
            this.resourceKey,
            {position: order},
            {...this.queryOptions, action: 'order', id}
        ).then(action(() => {
            this.ordering = false;
            this.structureStrategy.order(id, order);
        }));
    }

    @action search(searchTerm: ?string) {
        if (searchTerm === this.searchTerm.get()) {
            return;
        }

        this.searchTerm.set(searchTerm);
    }

    @action filter(filter: {[string]: mixed}) {
        this.filterOptions.set(filter);
    }

    @action select(row: Object) {
        // TODO do not hardcode id but use metdata instead
        if (this.selections.findIndex((item) => item.id === row.id) !== -1) {
            return;
        }

        this.selections.push(row);
    }

    /**
     * @deprecated
     */
    @action selectVisibleItems() {
        log.warn(
            'The "selectVisibleItems" method will select disabled rows. ' +
            'Therefore the method is deprecated since version 2.0. ' +
            'Use the "visibleItems" property and the "select" method instead.'
        );

        this.visibleItems.forEach((item) => {
            this.select(item);
        });
    }

    @action deselect(row: Object) {
        // TODO do not hardcode id but use metdata instead
        this.deselectById(row.id);
    }

    @action deselectById(id: string | number) {
        // TODO do not hardcode id but use metdata instead
        const index = this.selections.findIndex((item) => item.id === id);
        if (index === -1) {
            return;
        }

        this.selections.splice(index, 1);
    }

    /**
     * @deprecated
     */
    @action deselectVisibleItems() {
        log.warn(
            'The "deselectVisibleItems" method will deselect disabled rows. ' +
            'Therefore the method is deprecated since version 2.0. ' +
            'Use the "visibleItems" property and the "deselect" method instead.'
        );

        this.visibleItems.forEach((item) => {
            this.deselect(item);
        });
    }

    @computed get selectionIds(): Array<string | number> {
        // TODO do not hardcode id but use metdata instead
        return this.selections.map((item) => item.id);
    }

    @action clearSelection() {
        this.selections = [];
    }

    destroy() {
        this.sendRequestDisposer();
        this.searchDisposer();
        this.filterDisposer();
        this.sortColumnDisposer();
        this.sortOrderDisposer();
        this.limitDisposer();

        this.activeSettingDisposer();

        if (this.localeDisposer) {
            this.localeDisposer();
        }
    }
}
