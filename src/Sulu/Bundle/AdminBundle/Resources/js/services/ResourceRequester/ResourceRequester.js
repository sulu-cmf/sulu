// @flow
import Requester from '../Requester';
import resourceRouteRegistry from './registries/resourceRouteRegistry';
import type {ListOptions} from './types';

export default class ResourceRequester {
    static get(resourceKey: string, parameters: ?Object) {
        return Requester.get(resourceRouteRegistry.getUrl('detail', resourceKey, {...parameters}));
    }

    static post(resourceKey: string, data: ?Object, parameters: ?Object) {
        return Requester.post(resourceRouteRegistry.getUrl('detail', resourceKey, {...parameters}), data);
    }

    static put(resourceKey: string, data: ?Object, parameters: ?Object) {
        return Requester.put(resourceRouteRegistry.getUrl('detail', resourceKey, {...parameters}), data);
    }

    static patch(resourceKey: string, data: Object, parameters: ?Object) {
        return Requester.patch(resourceRouteRegistry.getUrl('detail', resourceKey, {...parameters}), data);
    }

    static patchList(resourceKey: string, data: Array<Object>) {
        return Requester.patch(resourceRouteRegistry.getUrl('list', resourceKey), data);
    }

    static getList(resourceKey: string, options: ListOptions = {}) {
        return Requester.get(resourceRouteRegistry.getUrl('list', resourceKey, {...options, flat: true}));
    }

    static delete(resourceKey: string, parameters: ?Object) {
        return Requester.delete(resourceRouteRegistry.getUrl('detail', resourceKey, {...parameters}));
    }

    static deleteList(resourceKey: string, parameters: Object){
        return Requester.delete(resourceRouteRegistry.getUrl('list', resourceKey, parameters));
    }
}
