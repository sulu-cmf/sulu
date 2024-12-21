// @flow
import {isArrayLike, toJS} from 'mobx';
import symfonyRouting from 'fos-jsrouting/router';
import log from 'loglevel';
import {transformDateForUrl} from '../../../utils/Date';
import type {EndpointConfiguration} from '../types';

function transformParameter(parameter) {
    return isArrayLike(parameter)
        ? parameter.map(transformParameter).join(',')
        : parameter instanceof Date
            ? transformDateForUrl(parameter)
            : parameter instanceof Object ? transformParameters(parameter) : toJS(parameter);
}

function transformParameters(parameters) {
    return Object.keys(parameters)
        .filter((parameterKey) => parameters[parameterKey] !== undefined)
        .reduce((transformedParameters, parameterKey) => {
            const value = toJS(parameters[parameterKey]);

            transformedParameters[parameterKey] = transformParameter(value);
            return transformedParameters;
        }, {});
}

class ResourceRouteRegistry {
    endpoints: EndpointConfiguration = {};

    configurationPromises: {[string]: Promise<Object>} = {};

    clear() {
        this.endpoints = {};
        this.configurationPromises = {};
    }

    setRoutingData(data: Object) {
        symfonyRouting.setRoutingData(data);
    }

    setEndpoints(endpoints: EndpointConfiguration) {
        this.endpoints = endpoints;
    }

    // @deprecated
    getDetailUrl(resourceKey: string, parameters: Object = {}) {
        log.warn(
            'The "getDetailUrl" method is deprecated since version 2.6 and will be removed. ' +
            'Use the "getUrl" option instead.'
        );

        return this.getUrl('detail', resourceKey, parameters);
    }

    // @deprecated
    getListUrl(resourceKey: string, parameters: Object = {}) {
        log.warn(
            'The "getListUrl" method is deprecated since version 2.6 and will be removed. ' +
            'Use the "getUrl" option instead.'
        );

        return this.getUrl('list', resourceKey, parameters);
    }

    getUrl(type: string, resourceKey: string, parameters: Object = {}) {
        if (!this.endpoints[resourceKey]) {
            throw new Error(
                'There are no routes for the resourceKey "' + resourceKey + '"!' +
                '\n\nRegistered keys: ' + Object.keys(this.endpoints).sort().join(', ')
            );
        }

        if (!this.endpoints[resourceKey].routes[type]) {
            throw new Error('There is no "' + type + '" route for the resourceKey "' + resourceKey + '"');
        }

        return symfonyRouting.generate(
            this.endpoints[resourceKey].routes[type],
            transformParameters(parameters)
        );
    }
}

export default new ResourceRouteRegistry();
