// @flow
import React from 'react';
import {autorun, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Loader from '../../components/Loader';
import Tabs from '../../views/Tabs';
import ResourceStore from '../../stores/ResourceStore';
import {Route} from '../../services/Router';
import resourceTabsStyles from './resourceTabs.scss';
import type {AttributeMap} from '../../services/Router';
import type {ViewProps} from '../../containers/ViewRenderer';

type Props = {
    ...ViewProps,
    locales?: Array<string>,
    titleProperty?: string,
};

@observer
class ResourceTabs extends React.Component<Props> {
    resourceStore: ResourceStore;
    reloadResourceStoreOnRouteChangeDisposer: () => void;
    disposeCreateResourceStoreOnRouteChangeDisposer: () => void;
    createResourceStoreDisposer: () => void;

    @computed get router() {
        return this.props.router;
    }

    @computed get route() {
        return this.props.route;
    }

    @computed get id() {
        const {
            router: {
                attributes: {
                    id,
                },
            },
        } = this.props;

        if (id !== undefined && typeof id !== 'string' && typeof id !== 'number') {
            throw new Error('The "id" router attribute must be a string or a number if given!');
        }

        return id;
    }

    @computed get resourceKey() {
        const {
            route: {
                options: {
                    resourceKey,
                },
            },
        } = this.props;

        if (!resourceKey) {
            throw new Error('The route does not define the mandatory "resourceKey" option');
        }

        return resourceKey;
    }

    constructor(props: Props) {
        super(props);

        this.createResourceStoreDisposer = autorun(this.createResourceStore);

        this.disposeCreateResourceStoreOnRouteChangeDisposer = this.router.addUpdateRouteHook(
            this.disposeCreateResourceStoreOnRouteChange
        );

        this.reloadResourceStoreOnRouteChangeDisposer = this.router.addUpdateRouteHook(
            this.reloadResourceStoreOnRouteChange
        );
    }

    createResourceStore = () => {
        const options = {};
        if (this.locales) {
            options.locale = observable.box();
            this.router.bind('locale', options.locale);
        }

        if (this.resourceStore) {
            this.resourceStore.destroy();
        }

        let {
            router: {
                attributes,
                route: {
                    options: {
                        requestParameters = {},
                        routerAttributesToFormRequest = {},
                    },
                },
            },
        } = this.props;

        const formStoreOptions = requestParameters ? requestParameters : {};

        routerAttributesToFormRequest = toJS(routerAttributesToFormRequest);
        Object.keys(toJS(routerAttributesToFormRequest)).forEach((key) => {
            const formOptionKey = routerAttributesToFormRequest[key];
            const attributeName = isNaN(key) ? key : routerAttributesToFormRequest[key];

            formStoreOptions[formOptionKey] = attributes[attributeName];
        });

        this.resourceStore = new ResourceStore(this.resourceKey, this.id, options, formStoreOptions);
    };

    disposeCreateResourceStoreOnRouteChange = (route: ?Route) => {
        // This only works for the first level of childs
        if (!this.route.children.includes(route) && this.route !== route) {
            // Avoid loading data for the old resourceKey with the new ID when switching to a different form
            this.createResourceStoreDisposer();
        }

        return true;
    };

    reloadResourceStoreOnRouteChange = (route: ?Route, attributes: ?AttributeMap) => {
        if (attributes && this.id !== attributes.id) {
            // No reload necessary, because if the ID changes the resourceStore itself will reload
            return true;
        }

        if (this.router.route === this.route || this.router.route === route) {
            return true;
        }

        // This only works for the first level of childs
        if (this.route.children.includes(route) || this.route === route) {
            this.resourceStore.reload();
        }

        return true;
    };

    componentWillUnmount() {
        this.resourceStore.destroy();
        this.reloadResourceStoreOnRouteChangeDisposer();
        this.createResourceStoreDisposer();
        this.disposeCreateResourceStoreOnRouteChangeDisposer();
    }

    @computed.struct get locales() {
        const {
            locales: propsLocales,
            route: {
                options: {
                    locales: routeLocales,
                },
            },
        } = this.props;

        return routeLocales ? routeLocales : propsLocales;
    }

    @computed get title() {
        const {
            route: {
                options: {
                    titleProperty: routeTitleProperty,
                },
            },
            titleProperty,
        } = this.props;

        if (!this.resourceStore.initialized && this.resourceStore.loading) {
            return undefined;
        }

        return this.resourceStore.data[titleProperty || routeTitleProperty];
    }

    @computed get sortedTabRoutes(): Array<Object> {
        const {route} = this.props;

        return route.children.concat()
            .sort((childRoute1, childRoute2) => {
                const {tabOrder: tabOrder1 = 0} = childRoute1.options;
                const {tabOrder: tabOrder2 = 0} = childRoute2.options;

                return tabOrder1 - tabOrder2;
            });
    }

    @computed get visibleTabRoutes(): Array<Object> {
        const data = toJS(this.resourceStore.data);

        return this.sortedTabRoutes
            .filter((childRoute) => {
                const {
                    options: {
                        tabCondition,
                    },
                } = childRoute;

                return !tabCondition || jexl.evalSync(tabCondition, data);
            });
    }

    render() {
        const {children} = this.props;

        const childComponent = children
            ? children({locales: this.locales, resourceStore: this.resourceStore, title: this.title})
            : null;

        const selectedRouteIndex = childComponent
            ? this.visibleTabRoutes.findIndex((childRoute) => childRoute === childComponent.props.route)
            : undefined;

        return this.resourceStore.initialized
            ? (
                <Tabs {...this.props} routeChildren={this.visibleTabRoutes} selectedIndex={selectedRouteIndex}>
                    {() => childComponent}
                </Tabs>
            )
            : (
                <div className={resourceTabsStyles.loader}>
                    <Loader />
                </div>
            );
    }
}

export default ResourceTabs;
