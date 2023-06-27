// @flow
import type {ResourceViewsMap} from '../types';

class ResourceViewRegistry {
    resourceViews: ResourceViewsMap;

    constructor() {
        this.clear();
    }

    clear() {
        this.resourceViews = {};
    }

    addResourceViews(resourceViews: ResourceViewsMap) {
        Object.keys(resourceViews).forEach((resourceKey) => {
            if (this.resourceViews[resourceKey]) {
                throw new Error('The resource views for "' + resourceKey + '" has already be configured.');
            }

            this.resourceViews[resourceKey] = resourceViews[resourceKey];
        });
    }

    has(view: string, resourceKey: string): boolean {
        if (!this.resourceViews[resourceKey]) {
            return false;
        }

        return this.resourceViews[resourceKey].views[view] !== undefined;
    }

    get(view: string, resourceKey: string): string {
        if (!this.resourceViews[resourceKey] && !this.resourceViews[resourceKey][view]) {
            throw new Error('The resource view "' + view + '" for "' + resourceKey + '" was not found.');
        }

        return this.resourceViews[resourceKey].views[view];
    }
}

export default new ResourceViewRegistry();
