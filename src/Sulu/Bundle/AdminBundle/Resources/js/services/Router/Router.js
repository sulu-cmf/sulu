// @flow
import {action, autorun, computed, isArrayLike, observable, toJS} from 'mobx';
import equal from 'fast-deep-equal';
import log from 'loglevel';
import {compile} from 'path-to-regexp';
import {parsePath} from 'history';
import {transformDateForUrl} from '../../utils/Date';
import routeRegistry from './registries/routeRegistry';
import resourceViewRegistry from './registries/resourceViewRegistry';
import Route from './Route';
import type {AttributeMap, UpdateAttributesHook, UpdateRouteHook, UpdateRouteMethod} from './types';
import type {IObservableValue} from 'mobx/lib/mobx';

const OBJECT_DELIMITER = '.';

function tryParse(value: ?string) {
    if (value === 'true') {
        return true;
    }

    if (value === 'false') {
        return false;
    }

    if (value === 'undefined') {
        return undefined;
    }

    if (value && value.match(/^\d\d\d\d-\d\d-\d\d$/)) {
        const date = new Date(value + ' 00:00'); // The time is necessary to avoid timezone issues
        if (date.toString() !== 'Invalid Date') {
            return date;
        }
    }

    if (value && value.match(/^\d\d\d\d-\d\d-\d\d \d\d:\d\d$/)) {
        const date = new Date(value);
        if (date.toString() !== 'Invalid Date') {
            return date;
        }
    }

    if (isNaN(value)) {
        return value;
    }

    if (value && value.match(/0[^.].*/)) {
        return value; // do not parse as number if string starts with 0 and does not contain a dot
    }

    return parseFloat(value);
}

function equalBindings(value1, value2) {
    if (typeof(value1) !== 'object' || typeof(value2) !== 'object') {
        // Type unsafe comparison to not trigger a new navigation when only data type changes
        return value1 == value2;
    }

    if (value1 instanceof Date && value2 instanceof Date) {
        return value1.getTime() === value2.getTime();
    }

    const objectKeys = Object.keys(value1);

    if (!equal(objectKeys, Object.keys(value2))) {
        return false;
    }

    return objectKeys.every((key) => equalBindings(value1[key], value2[key]));
}

function addValueToSearchParameters(searchParameters: URLSearchParams, value: Object, path: string) {
    if (isArrayLike(value)) {
        addArrayToSearchParameters(searchParameters, value, path);
    } else if (value instanceof Date) {
        addDateToSearchParameters(searchParameters, value, path);
    } else if (typeof value === 'object') {
        addObjectToSearchParameters(searchParameters, value, path);
    } else {
        searchParameters.set(path, value);
    }
}

function addArrayToSearchParameters(searchParameters: URLSearchParams, values: Array<*>, path: string) {
    values.forEach((value, index) => {
        addValueToSearchParameters(searchParameters, value, path + '[' + index + ']');
    });
}

function addDateToSearchParameters(searchParameters: URLSearchParams, value: Date, path: string) {
    searchParameters.set(path, transformDateForUrl(value));
}

function addObjectToSearchParameters(searchParameters: URLSearchParams, value: Object, path: string) {
    for (const key in value) {
        const childPath = path + OBJECT_DELIMITER + key;
        addValueToSearchParameters(searchParameters, value[key], childPath);
    }
}

function addAttributesFromSearchParameters(attributes: Object, value: string, key: string) {
    if (key.includes(OBJECT_DELIMITER)) {
        const keyParts = key.split(OBJECT_DELIMITER);
        if (!attributes[keyParts[0]]) {
            attributes[keyParts[0]] = {};
        }

        addAttributesFromSearchParameters(attributes[keyParts[0]], value, keyParts.slice(1).join(OBJECT_DELIMITER));
    } else if (key.includes('[') && key.includes(']')) {
        const arrayKey = key.slice(0, key.indexOf('['));

        if (!attributes[arrayKey]) {
            attributes[arrayKey] = [];
        }

        attributes[arrayKey].push(tryParse(value));
    } else {
        attributes[key] = tryParse(value);
    }
}

export default class Router {
    history: Object;
    @observable route: Route;
    @observable attributes: AttributeMap = {};
    @observable bindings: Map<string, IObservableValue<*>> = new Map();
    bindingDefaults: Map<string, ?string | number | boolean> = new Map();
    attributesHistory: {[string]: Array<AttributeMap>} = {};
    updateRouteHooks: {[priority: number]: Array<UpdateRouteHook>} = {};
    updateAttributesHooks: Array<UpdateAttributesHook> = [];
    redirectFlag: boolean = false;

    constructor(history: Object) {
        this.history = history;

        this.history.listen(({location}) => {
            log.info('URL was changed to "' + location.pathname + location.search + '"');
            this.match(location.pathname, location.search);
        });

        autorun(() => {
            const {pathname, search} = this.history.location;
            const currentUrl = this.url;
            const historyUrl = pathname + search;
            if (currentUrl !== historyUrl) {
                // have to use the historyUrl as a fallback, because currentUrl could be undefined and break the routing
                const url = currentUrl || historyUrl;
                log.info('Router changes URL to "' + url + '"' + (this.redirectFlag ? ' replacing history' : ''));
                const newLocation = {search: '', ...parsePath(url)};
                this.redirectFlag ? this.history.replace(newLocation) : this.history.push(newLocation);
                this.redirectFlag = false;
            }
        });

        window.addEventListener('beforeunload', (event) => {
            if (this.sortedUpdateRouteHooks.some((updateRouteHook) => updateRouteHook() === false)) {
                event.preventDefault();
                event.returnValue = true;
            }
        });
    }

    @computed get sortedUpdateRouteHooks() {
        return Object.keys(this.updateRouteHooks)
            .sort((a, b) => ((b: any): number) - ((a: any): number))
            .reduce((sortedUpdateRouteHooks, priority) => {
                sortedUpdateRouteHooks = [
                    ...sortedUpdateRouteHooks,
                    ...this.updateRouteHooks[((priority: any): number)],
                ];
                return sortedUpdateRouteHooks;
            }, []);
    }

    addUpdateRouteHook(hook: UpdateRouteHook, priority: number = 0) {
        if (!this.updateRouteHooks[priority]) {
            this.updateRouteHooks[priority] = [];
        }

        this.updateRouteHooks[priority].push(hook);

        return () => {
            const updateRouteHooksForPriority = this.updateRouteHooks[priority];

            const hookIndex = updateRouteHooksForPriority.indexOf(hook);
            if (hookIndex === -1) {
                return;
            }

            updateRouteHooksForPriority.splice(hookIndex, 1);
        };
    }

    addUpdateAttributesHook(hook: UpdateAttributesHook) {
        this.updateAttributesHooks.push(hook);
    }

    @action bind(
        key: string,
        value: IObservableValue<*>,
        defaultValue: ?string | number | boolean | Object = undefined
    ) {
        this.bindings.set(key, value);
        this.bindingDefaults.set(key, defaultValue);

        if (this.attributes[key] === undefined && value.get() === defaultValue) {
            // when the bound parameter already has the default value set, and the passed attribute has a value of
            // undefined, then we should not set it to undefined to set it back to the default value afterwards
            // if we would to that, registered intercepts would be called, although nothing changed
            return;
        }

        if (key in this.attributes && value.get() !== this.attributes[key]) {
            // when the bound parameter is bound set the state of the passed observable to the current value once
            // required because otherwise the parameter will be overridden on the initial start of the application
            value.set(this.attributes[key]);
        }

        if (value.get() === undefined) {
            // when the observable value is not set we want it to be the default value
            value.set(defaultValue);
        }
    }

    @action clearBindings() {
        this.bindings.clear();
        this.bindingDefaults.clear();
    }

    reload = () => {
        this.match(this.history.location.pathname, this.history.location.search);
    };

    reset = () => {
        this.history.replace({search: '', ...parsePath('/')});
    };

    @action match(path: string, queryString: string) {
        for (const name in routeRegistry.getAll()) {
            const route = routeRegistry.get(name);
            const match = route.regexp.exec(path);

            if (!match) {
                continue;
            }

            const {availableAttributes} = route;

            const attributes = {};
            for (let i = 1; i < match.length; i++) {
                attributes[availableAttributes[i - 1]] = tryParse(match[i]);
            }

            const search = new URLSearchParams(queryString);
            search.forEach((value, key) => {
                addAttributesFromSearchParameters(attributes, value, key);
            });

            this.handleNavigation(name, attributes, this.navigate);

            return;
        }

        const attributes = {};
        const search = new URLSearchParams(queryString);
        search.forEach((value, key) => {
            attributes[key] = tryParse(value);
        });

        this.attributes = attributes;
    }

    handleNavigation(name: string, attributes: Object, updateRouteMethod: UpdateRouteMethod): void {
        if (!this.isRouteChanging(name, attributes)) {
            return;
        }

        this.createAttributesHistory();
        this.update(name, attributes, updateRouteMethod);
    }

    @action navigate = (name: string, attributes: Object = {}): void => {
        this.handleNavigation(name, attributes, this.navigate);
    };

    @action navigateToResourceView = (view: string, resourceKey: string, attributes: Object = {}): void => {
        const route = resourceViewRegistry.get(view, resourceKey);

        this.navigate(route, attributes);
    };

    @action hasResourceView = (view: string, resourceKey: string): boolean => {
        return resourceViewRegistry.has(view, resourceKey);
    };

    @action redirect = (name: string, attributes: Object = {}): void => {
        this.redirectFlag = true;
        this.handleNavigation(name, attributes, this.redirect);
    };

    restore = (name: string, attributes: Object = {}): void => {
        if (!this.attributesHistory[name] || this.attributesHistory[name].length === 0) {
            this.update(name, attributes, this.restore);
            return;
        }

        if (!this.isRouteChanging(name, attributes)) {
            return;
        }

        const attributesHistory = this.attributesHistory[name].pop();

        this.update(name, {...attributesHistory, ...attributes}, this.restore);
    };

    @action update(name: string, attributes: Object, updateRouteMethod: UpdateRouteMethod): void {
        const route = routeRegistry.get(name);

        const updatedAttributes = {
            ...this.updateAttributesHooks.reduce((hookAttributes: Object, updateAttributeHook) => ({
                ...updateAttributeHook(route, attributes),
                ...hookAttributes,
            }), {}),
            ...attributes,
        };

        const attributeDefaults = route.attributeDefaults;
        Object.keys(attributeDefaults).forEach((key) => {
            // set default attributes if not passed, to automatically set important omitted attributes everywhere
            // e.g. allows to always pass the default locale if nothing is passed
            if (updatedAttributes[key] !== undefined) {
                return;
            }
            updatedAttributes[key] = attributeDefaults[key];
        });

        for (const updateRouteHook of this.sortedUpdateRouteHooks) {
            if (!updateRouteHook(route, updatedAttributes, updateRouteMethod)) {
                return;
            }
        }

        this.route = route;
        this.attributes = updatedAttributes;

        for (const [key, observableValue] of this.bindings.entries()) {
            const value: any = this.attributes[key] !== undefined
                ? this.attributes[key]
                : this.bindingDefaults.get(key);

            if (!equalBindings(toJS(value), toJS(observableValue.get()))) {
                observableValue.set(value);
            }
        }
    }

    @computed get url(): string {
        if (!this.route) {
            return '';
        }

        const attributes = toJS(this.attributes);
        for (const [key, observableValue] of this.bindings.entries()) {
            const value = observableValue.get();
            attributes[key] = value;
        }

        const url = compile(this.route.path)(attributes);
        const searchParameters = new URLSearchParams();
        const {availableAttributes} = this.route;
        Object.keys(attributes).forEach((key) => {
            const value = toJS(attributes[key]);
            if (availableAttributes.includes(key) || value == this.bindingDefaults.get(key)) {
                return;
            }

            addValueToSearchParameters(searchParameters, value, key);
        });

        const queryString = searchParameters.toString();

        return url + (queryString ? '?' + queryString : '');
    }

    createAttributesHistory() {
        if (!this.route) {
            return;
        }

        if (!(this.route.name in this.attributesHistory)) {
            this.attributesHistory[this.route.name] = [];
        }

        this.attributesHistory[this.route.name].push(toJS(this.attributes));
    }

    isRouteChanging(name: string, attributes: Object) {
        const route = routeRegistry.get(name);

        return !(
            this.route
            && this.route.name === route.name
            && equal(this.attributes, attributes)
        );
    }
}
