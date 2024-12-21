// @flow
import Route from './Route';

export type RouteConfig = {|
    attributeDefaults?: AttributeMap,
    name: string,
    options?: Object,
    parent?: string,
    path: string,
    rerenderAttributes?: Array<string>,
    type: string,
|};

export type AttributeMap = {[string]: ?mixed};

export type RouteMap = {[string]: Route};

export type UpdateAttributesHook = (route: Route, attributes: AttributeMap) => AttributeMap;

export type UpdateRouteHook = (
    route: ?Route,
    attributes: ?AttributeMap,
    updateRouteMethod: ?UpdateRouteMethod
) => boolean;

export type UpdateRouteMethod = (route: string, attributes: AttributeMap) => void;

export type ResourceViews = {
    views: {
        detail?: string,
        list?: string,
    },
};

export type ResourceViewsMap = {
    [string]: ResourceViews,
};
