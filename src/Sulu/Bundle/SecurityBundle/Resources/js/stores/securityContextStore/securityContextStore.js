// @flow
import type {Actions, SecurityContextGroups, Systems} from './types';

class SecurityContextStore {
    suluSecuritySystem: string;
    securityContexts: Systems;

    // TODO Could be removed by using resourceKey for security as well instead of separate security key
    resourceKeyMapping: {[resourceKey: string]: string};

    getSystems(): Array<string> {
        return Object.keys(this.securityContexts);
    }

    setSecurityContexts(securityContexts: Systems) {
        this.securityContexts = securityContexts;
    }

    getSecurityContextByResourceKey(resourceKey: string) {
        return this.resourceKeyMapping[resourceKey];
    }

    getSecurityContextGroups(system: string): SecurityContextGroups {
        return this.securityContexts[system];
    }

    getAvailableActions(resourceKey: string, system: ?string): Actions {
        const securitySystems = this.securityContexts[system || this.suluSecuritySystem];

        if (!securitySystems) {
            return [];
        }

        for (const groupKey in securitySystems) {
            const group = securitySystems[groupKey];
            for (const permissionKey in group) {
                if (permissionKey === this.resourceKeyMapping[resourceKey]) {
                    return group[permissionKey];
                }
            }
        }

        return [];
    }
}

export default new SecurityContextStore();
